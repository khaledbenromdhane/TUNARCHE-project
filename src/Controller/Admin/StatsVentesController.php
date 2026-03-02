<?php

namespace App\Controller\Admin;

use App\Repository\OeuvreRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/stats', name: 'app_admin_stats_')]
class StatsVentesController extends AbstractController
{
    #[Route('/ventes', name: 'ventes', methods: ['GET'])]
    public function ventes(OeuvreRepository $oeuvreRepository): Response
    {
        $stats = $oeuvreRepository->getStatsVentes();
        return $this->render('admin/stats_ventes.html.twig', [
            'stats' => $stats,
        ]);
    }
}
