<?php

namespace App\Controller;

use App\Repository\OeuvreRepository;
use App\Repository\EvenementRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * FrontController – Handles all frontoffice (public) routes under /front.
 */
#[Route('/front', name: 'app_front_')]
class FrontController extends AbstractController
{
    #[Route('/oeuvre', name: 'oeuvre', methods: ['GET'])]
    public function oeuvre(Request $request, OeuvreRepository $oeuvreRepository): Response
    {
        $filtre = $request->query->get('filtre');
        if (!in_array($filtre, ['disponible', 'vendue'], true)) {
            $filtre = null;
        }
        $oeuvres = $oeuvreRepository->findForFront($filtre);
        return $this->render('front/oeuvre.html.twig', [
            'oeuvres' => $oeuvres,
            'filtre' => $filtre,
        ]);
    }

    #[Route('/oeuvre/{id}', name: 'oeuvre_detail', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function oeuvreDetail(int $id, OeuvreRepository $oeuvreRepository): Response
    {
        $oeuvre = $oeuvreRepository->find($id);
        if (!$oeuvre) {
            throw $this->createNotFoundException('Œuvre non trouvée');
        }
        return $this->render('front/oeuvre_detail.html.twig', [
            'oeuvre' => $oeuvre,
        ]);
    }

    #[Route('', name: 'index', methods: ['GET'])]
    #[Route('/', name: 'home', methods: ['GET'])]
    public function index(EvenementRepository $evenementRepository): Response
    {
        $evenements = $evenementRepository->findBy([], ['date' => 'DESC'], 6);

        return $this->render('front/index.html.twig', [
            'evenements' => $evenements,
        ]);
    }

    #[Route('/contact', name: 'contact', methods: ['GET'])]
    public function contact(): Response
    {
        return $this->render('front/contact.html.twig');
    }

    #[Route('/about', name: 'about', methods: ['GET'])]
    public function about(): Response
    {
        return $this->render('front/about.html.twig');
    }

    #[Route('/blog', name: 'blog', methods: ['GET'])]
    public function blog(): Response
    {
        return $this->render('front/blog.html.twig');
    }

    #[Route('/blog-details', name: 'blog_details', methods: ['GET'])]
    public function blogDetails(): Response
    {
        return $this->render('front/blog-details.html.twig');
    }

    #[Route('/shop', name: 'shop', methods: ['GET'])]
    public function shop(): Response
    {
        return $this->render('front/shop.html.twig');
    }

    #[Route('/shop-2', name: 'shop2', methods: ['GET'])]
    public function shop2(): Response
    {
        return $this->render('front/shop-2.html.twig');
    }

    #[Route('/shop-details', name: 'shop_details', methods: ['GET'])]
    public function shopDetails(): Response
    {
        return $this->render('front/shop-details.html.twig');
    }

    #[Route('/team', name: 'team', methods: ['GET'])]
    public function team(): Response
    {
        return $this->render('front/team.html.twig');
    }

    #[Route('/team-details', name: 'team_details', methods: ['GET'])]
    public function teamDetails(): Response
    {
        return $this->render('front/team-details.html.twig');
    }

    #[Route('/project', name: 'project', methods: ['GET'])]
    public function project(): Response
    {
        return $this->render('front/project.html.twig');
    }

    #[Route('/project-2', name: 'project2', methods: ['GET'])]
    public function project2(): Response
    {
        return $this->render('front/project-2.html.twig');
    }

    #[Route('/project-3', name: 'project3', methods: ['GET'])]
    public function project3(): Response
    {
        return $this->render('front/project-3.html.twig');
    }

    #[Route('/project-4', name: 'project4', methods: ['GET'])]
    public function project4(): Response
    {
        return $this->render('front/project-4.html.twig');
    }

    #[Route('/project-details', name: 'project_details', methods: ['GET'])]
    public function projectDetails(): Response
    {
        return $this->render('front/project-details.html.twig');
    }

    #[Route('/event', name: 'event', methods: ['GET'])]
    public function event(): Response
    {
        return $this->render('front/event.html.twig');
    }

    #[Route('/event-2', name: 'event2', methods: ['GET'])]
    public function event2(): Response
    {
        return $this->render('front/event-2.html.twig');
    }

    #[Route('/event-3', name: 'event3', methods: ['GET'])]
    public function event3(): Response
    {
        return $this->render('front/event-3.html.twig');
    }

    #[Route('/event-4', name: 'event4', methods: ['GET'])]
    public function event4(): Response
    {
        return $this->render('front/event-4.html.twig');
    }

    #[Route('/event-details', name: 'event_details', methods: ['GET'])]
    public function eventDetails(): Response
    {
        return $this->render('front/event-details.html.twig');
    }

    #[Route('/opening-hour', name: 'opening_hour', methods: ['GET'])]
    public function openingHour(): Response
    {
        return $this->render('front/opening-hour.html.twig');
    }

    #[Route('/location', name: 'location', methods: ['GET'])]
    public function location(): Response
    {
        return $this->render('front/location.html.twig');
    }

    #[Route('/error', name: 'error', methods: ['GET'])]
    public function error(): Response
    {
        return $this->render('front/error.html.twig');
    }

    #[Route('/home-2', name: 'home2', methods: ['GET'])]
    public function home2(): Response
    {
        return $this->render('front/home-2.html.twig');
    }

    #[Route('/home-3', name: 'home3', methods: ['GET'])]
    public function home3(): Response
    {
        return $this->render('front/home-3.html.twig');
    }

    #[Route('/home-4', name: 'home4', methods: ['GET'])]
    public function home4(): Response
    {
        return $this->render('front/home-4.html.twig');
    }

    #[Route('/home-5', name: 'home5', methods: ['GET'])]
    public function home5(): Response
    {
        return $this->render('front/home-5.html.twig');
    }
}
