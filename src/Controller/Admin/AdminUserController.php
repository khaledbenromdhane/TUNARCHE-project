<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/admin/users')]
#[IsGranted('ROLE_ADMIN')]
class AdminUserController extends AbstractController
{
    #[Route('/', name: 'app_admin_users_index', methods: ['GET'])]
    public function index(UserRepository $userRepository): Response
    {
        $users = $userRepository->findAll();

        $stats = [
            'total'   => count($users),
            'admins'  => count(array_filter($users, fn($u) => in_array('ROLE_ADMIN', $u->getRoles()))),
            'artists' => count(array_filter($users, fn($u) => in_array('ROLE_ARTIST', $u->getRoles()) && !in_array('ROLE_ADMIN', $u->getRoles()))),
            'users'   => count(array_filter($users, fn($u) => !in_array('ROLE_ADMIN', $u->getRoles()) && !in_array('ROLE_ARTIST', $u->getRoles()))),
        ];

        return $this->render('admin/users/index.html.twig', [
            'users' => $users,
            'stats' => $stats,
        ]);
    }

    // ✅ Route /new AVANT /{id}
    #[Route('/new', name: 'app_admin_users_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
        SluggerInterface $slugger
    ): Response {
        $user = new User();
        $form = $this->createForm(UserType::class, $user, [
            'is_admin'          => true,
            'password_required' => true,
            'default_role'      => 'ROLE_USER',
            'show_avatar'       => true,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setRole($form->get('role')->getData());
            $plainPassword = $form->get('password')->getData();
            if ($plainPassword) {
                $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));
            }
            $avatarFile = $form->get('avatarFile')->getData();
            if ($avatarFile) {
                $filename = $this->uploadAvatar($avatarFile, $slugger);
                if ($filename) $user->setAvatarFilename($filename);
            }
            $em->persist($user);
            $em->flush();
            $this->addFlash('success', 'Utilisateur créé avec succès !');
            return $this->redirectToRoute('app_admin_users_index');
        }

        return $this->render('admin/users/new.html.twig', ['user' => $user, 'form' => $form]);
    }

    // ✅ Route /export-pdf AVANT /{id}
    #[Route('/export-pdf', name: 'app_admin_users_export_pdf', methods: ['GET'])]
    public function exportPdf(UserRepository $userRepository): Response
    {
        $users = $userRepository->findAll();

        $stats = [
            'total'   => count($users),
            'admins'  => count(array_filter($users, fn($u) => in_array('ROLE_ADMIN', $u->getRoles()))),
            'artists' => count(array_filter($users, fn($u) => in_array('ROLE_ARTIST', $u->getRoles()) && !in_array('ROLE_ADMIN', $u->getRoles()))),
            'users'   => count(array_filter($users, fn($u) => !in_array('ROLE_ADMIN', $u->getRoles()) && !in_array('ROLE_ARTIST', $u->getRoles()))),
        ];

        $html = $this->renderView('admin/users/pdf_export.html.twig', [
            'users'     => $users,
            'stats'     => $stats,
            'generated' => new \DateTime(),
        ]);

        return new Response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
        ]);
    }

    #[Route('/{id}', name: 'app_admin_users_show', methods: ['GET'])]
    public function show(User $user): Response
    {
        return $this->render('admin/users/show.html.twig', ['user' => $user]);
    }

    #[Route('/{id}/edit', name: 'app_admin_users_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        User $user,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
        SluggerInterface $slugger
    ): Response {
        $form = $this->createForm(UserType::class, $user, [
            'is_admin'          => true,
            'password_required' => false,
            'default_role'      => $user->getRole()[0] ?? 'ROLE_USER',
            'show_avatar'       => true,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setRole($form->get('role')->getData());
            $plainPassword = $form->get('password')->getData();
            if ($plainPassword) {
                $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));
            }
            $avatarFile = $form->get('avatarFile')->getData();
            if ($avatarFile) {
                $filename = $this->uploadAvatar($avatarFile, $slugger);
                if ($filename) $user->setAvatarFilename($filename);
            }
            $em->flush();
            $this->addFlash('success', 'Utilisateur modifié avec succès !');
            return $this->redirectToRoute('app_admin_users_index');
        }

        return $this->render('admin/users/edit.html.twig', ['user' => $user, 'form' => $form]);
    }

    #[Route('/{id}', name: 'app_admin_users_delete', methods: ['POST'])]
    public function delete(Request $request, User $user, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete' . $user->getId(), $request->request->get('_token'))) {
            $avatar = $user->getAvatarFilename();
            if ($avatar) {
                $path = $this->getParameter('kernel.project_dir') . '/public/uploads/avatars/' . $avatar;
                if (file_exists($path)) unlink($path);
            }
            $em->remove($user);
            $em->flush();
            $this->addFlash('success', 'Utilisateur supprimé.');
        }
        return $this->redirectToRoute('app_admin_users_index');
    }

    private function uploadAvatar($file, SluggerInterface $slugger): ?string
    {
        $safeFilename = $slugger->slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
        $newFilename  = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();
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
}
