<?php
namespace App\Controller;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class GoogleController extends AbstractController
{
    #[Route('/connect/google', name: 'connect_google_start')]
    public function connect(ClientRegistry $clientRegistry): Response
    {
        return $clientRegistry->getClient('google')->redirect(['profile', 'email'], []);
    }

    #[Route('/connect/google/check', name: 'connect_google_check')]
    public function check(): Response { return new Response(); }
}
