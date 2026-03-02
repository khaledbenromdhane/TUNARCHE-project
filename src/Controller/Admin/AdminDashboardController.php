<?php

namespace App\Controller\Admin;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminDashboardController extends AbstractController
{
    #[Route('/', name: 'app_admin_dashboard')]
    public function index(UserRepository $userRepository): Response
    {
        $users = $userRepository->findAll();

        $stats = [
            'total'   => count($users),
            'admins'  => count(array_filter($users, fn($u) => in_array('ROLE_ADMIN', $u->getRoles()))),
            'artists' => count(array_filter($users, fn($u) => in_array('ROLE_ARTIST', $u->getRoles()) && !in_array('ROLE_ADMIN', $u->getRoles()))),
            'users'   => count(array_filter($users, fn($u) => !in_array('ROLE_ADMIN', $u->getRoles()) && !in_array('ROLE_ARTIST', $u->getRoles()))),
        ];

        return $this->render('admin/dashboard/index.html.twig', [
            'stats' => $stats,
        ]);
    }
}