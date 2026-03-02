<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\String\Slugger\SluggerInterface;

class SecurityController extends AbstractController
{
    // ═══════════════════════════════════════════════════════════
    // CONNEXION
    // ✅ FIX : Symfony gère le hash automatiquement via security.yaml
    //          (bcrypt cost:12) — ne jamais hasher manuellement ici
    // ═══════════════════════════════════════════════════════════
    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->isGranted('ROLE_ADMIN')
                ? $this->redirectToRoute('app_admin_dashboard')
                : $this->redirectToRoute('app_home');
        }

        $error        = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error'         => $error,
        ]);
    }

    // ═══════════════════════════════════════════════════════════
    // INSCRIPTION
    // ✅ FIX CRITIQUE : utiliser UserPasswordHasherInterface
    //    et NON password_hash() PHP natif
    // ═══════════════════════════════════════════════════════════
    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
        SluggerInterface $slugger
    ): Response {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        $user = new User();
        $form = $this->createForm(UserType::class, $user, [
            'is_admin'         => false,
            'password_required'=> true,
            'default_role'     => 'ROLE_USER',
            'show_avatar'      => true,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // ── Vérification reCAPTCHA v3 ───────────────────────────
            $recaptchaToken    = $request->request->get('recaptcha_token');
            $recaptchaSecret   = $_ENV['RECAPTCHA_SECRET_KEY'] ?? '';
            $recaptchaResponse = $this->verifyRecaptcha($recaptchaToken, $recaptchaSecret);

            if (!$recaptchaResponse) {
                $this->addFlash('error', 'Vérification reCAPTCHA échouée. Réessayez.');
                return $this->render('security/register.html.twig', ['form' => $form]);
            }

            // ── Hash du mot de passe ( méthode Symfony correcte) ──
            $plainPassword  = $form->get('password')->getData();
            $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
            $user->setPassword($hashedPassword);

            // ── Rôle ────────────────────────────────────────────────
            $user->setRole($form->get('role')->getData());

            // ── Upload Avatar ────────────────────────────────────────
            $avatarFile = $form->get('avatarFile')->getData();
            if ($avatarFile) {
                $filename = $this->uploadAvatar($avatarFile, $slugger);
                if ($filename) {
                    $user->setAvatarFilename($filename);
                }
            }

            $em->persist($user);
            $em->flush();

            $this->addFlash('success', 'Compte créé avec succès ! Vous pouvez vous connecter.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/register.html.twig', ['form' => $form]);
    }

    // ═══════════════════════════════════════════════════════════
    // MOT DE PASSE OUBLIÉ — Étape 1 : Saisie email
    // ═══════════════════════════════════════════════════════════
    #[Route('/forgot-password', name: 'app_forgot_password')]
    public function forgotPassword(
        Request $request,
        EntityManagerInterface $em,
        MailerInterface $mailer,
        UrlGeneratorInterface $router
    ): Response {
        if ($request->isMethod('POST')) {
            $email = $request->request->get('email');
            $user  = $em->getRepository(User::class)->findOneBy(['email' => $email]);

            if ($user) {
                // Générer token sécurisé
                $token     = bin2hex(random_bytes(32));
                $expiresAt = new \DateTime('+1 hour');

                $user->setResetToken($token);
                $user->setResetTokenExpiresAt($expiresAt);
                $em->flush();

                // Générer lien absolu
                $resetLink = $router->generate(
                    'app_reset_password',
                    ['token' => $token],
                    UrlGeneratorInterface::ABSOLUTE_URL
                );

                // Envoyer l'email
                $emailMessage = (new Email())
                    ->from('noreply@artvista.museum')
                    ->to($user->getEmail())
                    ->subject('Réinitialisation de votre mot de passe - ArtVista')
                    ->html(
                        $this->renderView('emails/reset_password.html.twig', [
                            'user'       => $user,
                            'resetLink'  => $resetLink,
                            'expiresAt'  => $expiresAt,
                        ])
                    );

                try {
                    $mailer->send($emailMessage);
                    $this->addFlash('success', 'Un email de réinitialisation a été envoyé à ' . $email);
                } catch (\Exception $e) {
                    // En développement : afficher le lien dans le flash
                    $this->addFlash('info', 'Mode dev — Lien de reset : ' . $resetLink);
                }
            } else {
                // Sécurité : ne pas révéler si l'email existe
                $this->addFlash('success', 'Si cet email est enregistré, un lien vous sera envoyé.');
            }

            return $this->redirectToRoute('app_forgot_password');
        }

        return $this->render('security/forgot_password.html.twig');
    }

    // ═══════════════════════════════════════════════════════════
    // RESET MOT DE PASSE — Étape 2 : Nouveau mot de passe
    // ═══════════════════════════════════════════════════════════
    #[Route('/reset-password/{token}', name: 'app_reset_password')]
    public function resetPassword(
        string $token,
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $user = $em->getRepository(User::class)->findOneBy(['resetToken' => $token]);

        if (!$user || !$user->isResetTokenValid()) {
            $this->addFlash('error', 'Ce lien est invalide ou a expiré.');
            return $this->redirectToRoute('app_forgot_password');
        }

        if ($request->isMethod('POST')) {
            $newPassword     = $request->request->get('password');
            $confirmPassword = $request->request->get('password_confirm');

            if ($newPassword !== $confirmPassword) {
                $this->addFlash('error', 'Les mots de passe ne correspondent pas.');
                return $this->render('security/reset_password.html.twig', ['token' => $token]);
            }

            if (strlen($newPassword) < 8) {
                $this->addFlash('error', 'Le mot de passe doit contenir au moins 8 caractères.');
                return $this->render('security/reset_password.html.twig', ['token' => $token]);
            }

            // ✅ Hash correct
            $hashed = $passwordHasher->hashPassword($user, $newPassword);
            $user->setPassword($hashed);
            $user->setResetToken(null);
            $user->setResetTokenExpiresAt(null);
            $em->flush();

            $this->addFlash('success', 'Mot de passe réinitialisé ! Vous pouvez vous connecter.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/reset_password.html.twig', ['token' => $token]);
    }

    // ═══════════════════════════════════════════════════════════
    // DÉCONNEXION
    // ═══════════════════════════════════════════════════════════
    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('Intercepté par le firewall Symfony.');
    }

    // ═══════════════════════════════════════════════════════════
    // Helpers privés
    // ═══════════════════════════════════════════════════════════

    private function uploadAvatar($file, SluggerInterface $slugger): ?string
    {
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename     = $slugger->slug($originalFilename);
        $newFilename      = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();

        try {
            $file->move(
                $this->getParameter('kernel.project_dir') . '/public/uploads/avatars',
                $newFilename
            );
            return $newFilename;
        } catch (FileException $e) {
            return null;
        }
    }

    private function verifyRecaptcha(?string $token, string $secret): bool
    {
        if (empty($token) || empty($secret)) {
            // En développement sans clé configurée, on passe
            return true;
        }

        $response = file_get_contents(
            'https://www.google.com/recaptcha/api/siteverify?secret=' 
            . $secret . '&response=' . $token
        );
        $data = json_decode($response, true);
        return ($data['success'] ?? false) && ($data['score'] ?? 0) >= 0.5;
    }
}
