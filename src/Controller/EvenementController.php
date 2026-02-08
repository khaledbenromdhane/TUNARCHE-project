<?php

namespace App\Controller;

use App\Entity\Evenement;
use App\Entity\Participation;
use App\Repository\EvenementRepository;
use App\Repository\ParticipationRepository;
use App\Service\EvenementService;
use App\Service\ParticipationService;
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
    public function index(Request $request, EvenementRepository $repo, ParticipationRepository $participationRepo): Response
    {
        $q     = trim($request->query->get('q', ''));
        $sort  = $request->query->get('sort', 'date');
        $order = $request->query->get('order', 'DESC');

        $evenements = $repo->searchAndSort($q, '', '', $sort, $order);

        // Group participations by event ID for the template
        $participationsByEvent = [];
        foreach ($evenements as $evt) {
            $participationsByEvent[$evt->getId()] = $participationRepo->findByEvenement($evt->getId());
        }

        return $this->render('front/evenement.html.twig', [
            'evenements'            => $evenements,
            'participationsByEvent' => $participationsByEvent,
            'q'                     => $q,
            'sort'                  => $sort,
            'order'                 => $order,
        ]);
    }

    // ─── FRONT PARTICIPATION CREATE ────────────────────────

    #[Route('/participer', name: 'participate', methods: ['POST'])]
    public function participate(Request $request, ManagerRegistry $m, ParticipationService $service, EvenementRepository $evenementRepo): Response
    {
        $data = [
            'id_evenement'       => $request->request->get('id_evenement', ''),
            'date_participation' => $request->request->get('date_participation', ''),
            'nbr_participation'  => $request->request->get('nbr_participation', ''),
            'statut'             => $request->request->get('statut', ''),
            'mode_paiement'      => $request->request->get('mode_paiement', ''),
        ];

        $errors = $service->validate($data);

        if (!empty($errors)) {
            foreach ($errors as $field => $msg) {
                $this->addFlash('error', $msg);
            }
            return $this->redirectToRoute('app_evenement_index');
        }

        $em = $m->getManager();
        $participation = new Participation();

        $evenement = $evenementRepo->find((int)$data['id_evenement']);
        $participation->setEvenement($evenement);
        $participation->setDateParticipation(\DateTime::createFromFormat('Y-m-d', $data['date_participation']));
        $participation->setNbrParticipation((int)$data['nbr_participation']);
        $participation->setStatut($data['statut']);

        if ($evenement && $evenement->isPaiement() && !empty($data['mode_paiement'])) {
            $participation->setModePaiement($data['mode_paiement']);
        } else {
            $participation->setModePaiement(null);
        }

        $em->persist($participation);
        $em->flush();

        $this->addFlash('success', 'Participation créée avec succès !');
        return $this->redirectToRoute('app_evenement_index');
    }

    // ─── FRONT PARTICIPATION UPDATE ────────────────────────

    #[Route('/participation/{id}/modifier', name: 'participation_update', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function participationUpdate(int $id, Request $request, ParticipationRepository $participationRepo, ManagerRegistry $m, ParticipationService $service, EvenementRepository $evenementRepo): Response
    {
        $participation = $participationRepo->find($id);

        if (!$participation) {
            $this->addFlash('error', 'Participation introuvable.');
            return $this->redirectToRoute('app_evenement_index');
        }

        $data = [
            'id_evenement'       => $request->request->get('id_evenement', ''),
            'date_participation' => $request->request->get('date_participation', ''),
            'nbr_participation'  => $request->request->get('nbr_participation', ''),
            'statut'             => $request->request->get('statut', ''),
            'mode_paiement'      => $request->request->get('mode_paiement', ''),
        ];

        $errors = $service->validate($data, $participation->getId());

        if (!empty($errors)) {
            foreach ($errors as $field => $msg) {
                $this->addFlash('error', $msg);
            }
            return $this->redirectToRoute('app_evenement_index');
        }

        $em = $m->getManager();

        $evenement = $evenementRepo->find((int)$data['id_evenement']);
        $participation->setEvenement($evenement);
        $participation->setDateParticipation(\DateTime::createFromFormat('Y-m-d', $data['date_participation']));
        $participation->setNbrParticipation((int)$data['nbr_participation']);
        $participation->setStatut($data['statut']);

        if ($evenement && $evenement->isPaiement() && !empty($data['mode_paiement'])) {
            $participation->setModePaiement($data['mode_paiement']);
        } else {
            $participation->setModePaiement(null);
        }

        $em->persist($participation);
        $em->flush();

        $this->addFlash('success', 'Participation modifiée avec succès !');
        return $this->redirectToRoute('app_evenement_index');
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
        $data = [
            'nom'              => $request->request->get('nom', ''),
            'type_evenement'   => $request->request->get('type_evenement', ''),
            'nbr_participant'  => $request->request->get('nbr_participant', ''),
            'date'             => $request->request->get('date', ''),
            'heure'            => $request->request->get('heure', ''),
            'lieu'             => $request->request->get('lieu', ''),
            'description'      => $request->request->get('description', ''),
            'paiement'         => $request->request->has('paiement'),
        ];

        $imageFile = $request->files->get('image');
        if ($imageFile) {
            $data['image_file'] = $imageFile;
        }

        return $data;
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

        // Handle image upload
        if (!empty($data['image_file'])) {
            $file = $data['image_file'];
            $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/evenements';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0775, true);
            }
            // Remove old image if exists
            if ($evenement->getImage()) {
                $oldPath = $uploadDir . '/' . $evenement->getImage();
                if (file_exists($oldPath)) {
                    unlink($oldPath);
                }
            }
            $newFilename = uniqid('evt_') . '.' . $file->guessExtension();
            $file->move($uploadDir, $newFilename);
            $evenement->setImage($newFilename);
        }
    }
}
