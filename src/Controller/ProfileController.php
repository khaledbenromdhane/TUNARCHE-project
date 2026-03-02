<?php
namespace App\Controller;
use App\Entity\User;
use App\Form\ProfileType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/profile')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class ProfileController extends AbstractController
{
    #[Route('/', name: 'app_profile')]
    public function index(): Response
    {
        return $this->render('profile/index.html.twig', ['user' => $this->getUser()]);
    }

    #[Route('/edit', name: 'app_profile_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $hasher, SluggerInterface $slugger): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $form = $this->createForm(ProfileType::class, $user, [
    'csrf_token_id' => 'profile_edit',
]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $newPwd = $form->get('newPassword')->getData();
            if ($newPwd) $user->setPassword($hasher->hashPassword($user, $newPwd));
            $file = $form->get('avatarFile')->getData();
            if ($file) {
                $old = $user->getAvatarFilename();
                if ($old) { $p = $this->getParameter('kernel.project_dir').'/public/uploads/avatars/'.$old; if (file_exists($p)) unlink($p); }
                $name = $slugger->slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)).'-'.uniqid().'.'.$file->guessExtension();
                try { $file->move($this->getParameter('kernel.project_dir').'/public/uploads/avatars', $name); $user->setAvatarFilename($name); } catch (FileException) {}
            }
            $em->flush();
            $this->addFlash('success', 'Profil mis à jour !');
            return $this->redirectToRoute('app_profile');
        }
        return $this->render('profile/edit.html.twig', ['user' => $user, 'form' => $form]);
    }

    #[Route('/delete-avatar', name: 'app_profile_delete_avatar', methods: ['POST'])]
    public function deleteAvatar(Request $request, EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$this->isCsrfTokenValid('delete_avatar', $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.'); return $this->redirectToRoute('app_profile');
        }
        if ($f = $user->getAvatarFilename()) {
            $p = $this->getParameter('kernel.project_dir').'/public/uploads/avatars/'.$f;
            if (file_exists($p)) unlink($p);
            $user->setAvatarFilename(null); $em->flush();
            $this->addFlash('success', 'Photo supprimée.');
        }
        return $this->redirectToRoute('app_profile');
    }
}
