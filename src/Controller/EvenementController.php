<?php

namespace App\Controller;

use App\Entity\Evenement;
use App\Repository\EvenementRepository;
use App\Service\EvenementService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * EvenementController – Front-end CRUD for Evenement entity.
 * Uses ManagerRegistry for persistence and EvenementRepository for queries.
 * Form-based operations only 
 */
#[Route('/evenement', name: 'app_evenement_')]
class EvenementController extends AbstractController
{

    // ─── LIST (front-end page) ─────────────────────────────

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request, EvenementRepository $repo): Response
    {
        $q     = trim($request->query->get('q', ''));
        $sort  = $request->query->get('sort', 'date');
        $order = $request->query->get('order', 'DESC');

        $evenements = $repo->searchAndSort($q, '', '', $sort, $order);

        return $this->render('evenement/index.html.twig', [
            'evenements' => $evenements,
            'q'          => $q,
            'sort'       => $sort,
            'order'      => $order,
        ]);
    }

    // ─── CREATE (form-based, persisted to database) ────────

    #[Route('/ajouter', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, ManagerRegistry $m, EvenementService $service): Response
    {
        $errors = [];
        $data   = [];

        if ($request->isMethod('POST')) {
            $data   = $this->extractFormData($request);
            $errors = $service->validate($data);

            if (empty($errors)) {
                $em = $m->getManager();
                $evenement = new Evenement();
                $this->hydrate($evenement, $data);
                $em->persist($evenement);
                $em->flush();

                $this->addFlash('success', 'Événement créé avec succès !');
                return $this->redirectToRoute('app_evenement_index');
            }
        }

        return $this->render('evenement/new.html.twig', [
            'errors' => $errors,
            'data'   => $data,
            'types'  => Evenement::TYPES,
        ]);
    }

    // ─── SHOW (front-end page) ─────────────────────────────

    #[Route('/{id}', name: 'show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show($id, EvenementRepository $repo): Response
    {
        $evenement = $repo->find($id);

        if (!$evenement) {
            $this->addFlash('error', 'Événement introuvable.');
            return $this->redirectToRoute('app_evenement_index');
        }

        return $this->render('evenement/show.html.twig', [
            'evenement' => $evenement,
        ]);
    }

    // ─── EDIT (form-based, persisted to database) ──────────

    #[Route('/{id}/modifier', name: 'edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit($id, Request $request, EvenementRepository $repo, ManagerRegistry $m, EvenementService $service): Response
    {
        $evenement = $repo->find($id);

        if (!$evenement) {
            $this->addFlash('error', 'Événement introuvable.');
            return $this->redirectToRoute('app_evenement_index');
        }

        $errors = [];

        if ($request->isMethod('POST')) {
            $data   = $this->extractFormData($request);
            $errors = $service->validate($data);

            if (empty($errors)) {
                $em = $m->getManager();
                $this->hydrate($evenement, $data);
                $em->persist($evenement);
                $em->flush();

                $this->addFlash('success', 'Événement modifié avec succès !');
                return $this->redirectToRoute('app_evenement_index');
            }
        }

        return $this->render('evenement/edit.html.twig', [
            'evenement' => $evenement,
            'errors'    => $errors,
            'types'     => Evenement::TYPES,
        ]);
    }

    // ─── DELETE (form-based, removed from database) ────────

    #[Route('/{id}/supprimer', name: 'delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete($id, Request $request, EvenementRepository $repo, ManagerRegistry $m): Response
    {
        $evenement = $repo->find($id);

        if (!$evenement) {
            $this->addFlash('error', 'Événement introuvable.');
            return $this->redirectToRoute('app_evenement_index');
        }

        // Verify CSRF token
        $token = $request->request->get('_token', '');
        if (!$this->isCsrfTokenValid('delete_evenement_' . $id, $token)) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_evenement_index');
        }

        $em = $m->getManager();
        $em->remove($evenement);
        $em->flush();

        $this->addFlash('success', 'Événement supprimé avec succès !');

        return $this->redirectToRoute('app_evenement_index');
    }

    // ─── HELPERS ───────────────────────────────────────────

    /**
     * Extract form data from a POST request.
     */
    private function extractFormData(Request $request): array
    {
        return [
            'nom'              => $request->request->get('nom', ''),
            'type_evenement'   => $request->request->get('type_evenement', ''),
            'nbr_participant'  => $request->request->get('nbr_participant', ''),
            'date'             => $request->request->get('date', ''),
            'heure'            => $request->request->get('heure', ''),
            'lieu'             => $request->request->get('lieu', ''),
            'description'      => $request->request->get('description', ''),
            'paiement'         => $request->request->has('paiement'),
        ];
    }

    /**
     * Populate an Evenement entity from validated form data.
     */
    private function hydrate(Evenement $evenement, array $data): void
    {
        $evenement->setNom(trim($data['nom']));
        $evenement->setTypeEvenement($data['type_evenement']);
        $evenement->setNbrParticipant((int)$data['nbr_participant']);
        $evenement->setLieu(trim($data['lieu']));
        $evenement->setDescription(trim($data['description']));
        $evenement->setPaiement(!empty($data['paiement']));

        $dateObj = \DateTime::createFromFormat('Y-m-d', $data['date']);
        $evenement->setDate($dateObj);

        $heureObj = \DateTime::createFromFormat('H:i', $data['heure']);
        $evenement->setHeure($heureObj);
    }
}
