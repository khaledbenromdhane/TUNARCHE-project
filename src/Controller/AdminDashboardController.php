<?php

namespace App\Controller;

use App\Repository\FormationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminDashboardController extends AbstractController
{
    #[Route('/admin', name: 'app_admin_dashboard')]
    public function index(Request $request, FormationRepository $formationRepo): Response
    {
        // Récupération des paramètres de l'URL (GET)
        $searchTerm = $request->query->get('q');
        $typeFilter = $request->query->get('type');
        $sort = $request->query->get('sort', 'f.id');
        $direction = $request->query->get('dir', 'ASC');

        // Récupération des données filtrées
        $formations = $formationRepo->findBySearchAndFilter($searchTerm, $typeFilter, $sort, $direction);

        return $this->render('back/dashboard.html.twig', [
            'formations' => $formations,
        ]);
    }
}