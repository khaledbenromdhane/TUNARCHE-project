<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        // On peut passer des données aux sections Twig ici
        return $this->render('index.html.twig', [
            'controller_name' => 'HomeController',
        ]);
    }
}