<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * AdminController – Handles all backoffice admin panel routes.
 */
#[Route('/admin', name: 'app_admin_')]
class AdminController extends AbstractController
{
    /**
     * Dashboard – Main overview page with statistics and recent data.
     */
    #[Route('/', name: 'dashboard')]
    public function dashboard(): Response
    {
        return $this->render('admin/dashboard.html.twig');
    }

    /**
     * Users – Full user management page (list, add, edit, delete).
     */
    #[Route('/user', name: 'users')]
    public function users(): Response
    {
        return $this->render('admin/users.html.twig');
    }

    /**
     * Événements – Full event management page (list, add, edit, delete).
     */
    #[Route('/evenement', name: 'evenements')]
    public function evenements(): Response
    {
        return $this->render('admin/evenements.html.twig');
    }

    /**
     * Participations – Full participation management page (list, add, edit, delete).
     */
    #[Route('/participation', name: 'participations')]
    public function participations(): Response
    {
        return $this->render('admin/participations.html.twig');
    }

    /**
     * Œuvres – Alias vers CRUD œuvres
     */
    #[Route('/oeuvres', name: 'oeuvres')]
    public function oeuvres(): Response
    {
        return $this->redirectToRoute('app_admin_oeuvre_index');
    }

    /**
     * Formations – Full training/course management page (list, add, edit, delete).
     */
    #[Route('/formation', name: 'formations')]
    public function formations(): Response
    {
        return $this->render('admin/formations.html.twig');
    }

    /**
     * Évaluations – Full evaluation management page (list, add, edit, delete).
     */
    #[Route('/evaluation', name: 'evaluations')]
    public function evaluations(): Response
    {
        return $this->render('admin/evaluations.html.twig');
    }

    /**
     * Commentaires – Full comment management page (list, add, edit, delete).fffff
     */
    #[Route('/commentaire', name: 'commentaires')]
    public function commentaires(): Response
    {
        return $this->render('admin/commentaires.html.twig');
    }

    /**
     * Publications – Full publication management page (list, add, edit, delete).
     */
    #[Route('/publication', name: 'publications')]
    public function publications(): Response
    {
        return $this->render('admin/publications.html.twig');
    }
}
