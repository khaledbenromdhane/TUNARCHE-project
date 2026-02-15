<?php

namespace App\Controller;

use App\Entity\Evenement;
use App\Entity\Participation;
use App\Repository\EvenementRepository;
use App\Repository\ParticipationRepository;
use App\Service\EvenementService;
use App\Service\ParticipationService;
use App\Service\StripeService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
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
        $order = $request->query->get('order', 'ASC');

        $evenements = $repo->searchAndSort($q, '', '', $sort, $order);

        // Group participations by event ID for the template
        $participationsByEvent = [];
        foreach ($evenements as $evt) {
            $participationsByEvent[$evt->getId()] = $participationRepo->findByEvenement($evt->getId());
        }

        // Fetch validation errors from session
        $session = $request->getSession();
        $participateErrors = $session->get('participate_errors', []);
        $participateData = $session->get('participate_data', []);
        $editErrors = $session->get('edit_participation_errors', []);
        $editData = $session->get('edit_participation_data', []);
        $editParticipationId = $session->get('edit_participation_id', null);
        
        // Clear errors from session after fetching
        $session->remove('participate_errors');
        $session->remove('participate_data');
        $session->remove('edit_participation_errors');
        $session->remove('edit_participation_data');
        $session->remove('edit_participation_id');

        return $this->render('front/evenement.html.twig', [
            'evenements'            => $evenements,
            'participationsByEvent' => $participationsByEvent,
            'q'                     => $q,
            'sort'                  => $sort,
            'order'                 => $order,
            'participateErrors'     => $participateErrors,
            'participateData'       => $participateData,
            'editErrors'            => $editErrors,
            'editData'              => $editData,
            'editParticipationId'   => $editParticipationId,
        ]);
    }

    // ─── FRONT PARTICIPATION CREATE ────────────────────────

    #[Route('/participer', name: 'participate', methods: ['POST'])]
    public function participate(Request $request, ManagerRegistry $m, ParticipationService $service, EvenementRepository $evenementRepo, StripeService $stripe): Response
    {
        $isAjax = $request->headers->get('X-Requested-With') === 'XMLHttpRequest';
        $today = (new \DateTime('today'))->format('Y-m-d');

        $data = [
            'id_evenement'       => $request->request->get('id_evenement', ''),
            'date_participation' => $today,
            'nbr_participation'  => $request->request->get('nbr_participation', ''),
            'statut'             => 'En attente',
            'mode_paiement'      => $request->request->get('mode_paiement', ''),
        ];

        // Check that today is on or before the event date
        $evenement = $evenementRepo->find((int)$data['id_evenement']);
        if ($evenement && $evenement->getDate()) {
            $eventDate = $evenement->getDate()->format('Y-m-d');
            if ($today > $eventDate) {
                if ($isAjax) {
                    return $this->json(['success' => false, 'errors' => ['date' => 'Vous ne pouvez plus participer à cet événement car sa date est dépassée.']]);
                }
                $this->addFlash('error', 'Vous ne pouvez plus participer à cet événement car sa date est dépassée.');
                return $this->redirectToRoute('app_evenement_index');
            }
        }

        $errors = $service->validate($data);

        if (!empty($errors)) {
            if ($isAjax) {
                return $this->json(['success' => false, 'errors' => $errors]);
            }
            // Store errors and data in session to display under inputs
            $session = $request->getSession();
            $session->set('participate_errors', $errors);
            $session->set('participate_data', $data);
            return $this->redirectToRoute('app_evenement_index');
        }

        // ── STRIPE: If paid event + Carte → redirect to Stripe Checkout ──
        if ($evenement && $evenement->isPaiement() && $data['mode_paiement'] === 'Carte') {
            $session = $request->getSession();
            // Store participation data in session for after-payment creation
            $session->set('stripe_participation_data', $data);

            try {
                $checkoutSession = $stripe->createCheckoutSession(
                    $evenement,
                    (int)$data['nbr_participation']
                );
                if ($isAjax) {
                    return $this->json(['success' => true, 'redirect' => $checkoutSession->url]);
                }
                return $this->redirect($checkoutSession->url);
            } catch (\Exception $e) {
                if ($isAjax) {
                    return $this->json(['success' => false, 'errors' => ['general' => 'Erreur de paiement Stripe : ' . $e->getMessage()]]);
                }
                $this->addFlash('error', 'Erreur de paiement Stripe : ' . $e->getMessage());
                return $this->redirectToRoute('app_evenement_index');
            }
        }

        // ── Cash or free event: create participation immediately ──
        $em = $m->getManager();
        $participation = new Participation();

        $participation->setEvenement($evenement);
        $participation->setDateParticipation(\DateTime::createFromFormat('Y-m-d', $today));
        $participation->setNbrParticipation((int)$data['nbr_participation']);
        $participation->setStatut('En attente');

        if ($evenement && $evenement->isPaiement() && !empty($data['mode_paiement'])) {
            $participation->setModePaiement($data['mode_paiement']);
        } else {
            $participation->setModePaiement(null);
        }

        $em->persist($participation);
        $em->flush();

        if ($isAjax) {
            return $this->json(['success' => true, 'redirect' => $this->generateUrl('app_evenement_index')]);
        }
        $this->addFlash('success', 'Participation créée avec succès !');
        return $this->redirectToRoute('app_evenement_index');
    }

    // ─── STRIPE SUCCESS CALLBACK ───────────────────────────

    #[Route('/stripe/success', name: 'stripe_success', methods: ['GET'])]
    public function stripeSuccess(Request $request, ManagerRegistry $m, StripeService $stripe, EvenementRepository $evenementRepo): Response
    {
        $sessionId = $request->query->get('session_id');
        if (!$sessionId) {
            $this->addFlash('error', 'Session de paiement introuvable.');
            return $this->redirectToRoute('app_evenement_index');
        }

        try {
            $checkoutSession = $stripe->retrieveSession($sessionId);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Impossible de vérifier le paiement.');
            return $this->redirectToRoute('app_evenement_index');
        }

        if ($checkoutSession->payment_status !== 'paid') {
            $this->addFlash('error', 'Le paiement n\'a pas été finalisé.');
            return $this->redirectToRoute('app_evenement_index');
        }

        // Retrieve participation data from session
        $session = $request->getSession();
        $data = $session->get('stripe_participation_data');
        $session->remove('stripe_participation_data');

        if (!$data) {
            $this->addFlash('error', 'Données de participation expirées. Veuillez réessayer.');
            return $this->redirectToRoute('app_evenement_index');
        }

        $evenement = $evenementRepo->find((int)$data['id_evenement']);
        if (!$evenement) {
            $this->addFlash('error', 'Événement introuvable.');
            return $this->redirectToRoute('app_evenement_index');
        }

        $em = $m->getManager();
        $participation = new Participation();

        $participation->setEvenement($evenement);
        $participation->setDateParticipation(\DateTime::createFromFormat('Y-m-d', $data['date_participation']));
        $participation->setNbrParticipation((int)$data['nbr_participation']);
        $participation->setStatut('Confirmée');
        $participation->setModePaiement('Carte');

        $em->persist($participation);
        $em->flush();

        $this->addFlash('success', 'Paiement effectué avec succès ! Votre participation est confirmée.');
        return $this->redirectToRoute('app_evenement_index');
    }

    // ─── STRIPE CANCEL CALLBACK ────────────────────────────

    #[Route('/stripe/cancel', name: 'stripe_cancel', methods: ['GET'])]
    public function stripeCancel(Request $request): Response
    {
        // Clean up session data
        $request->getSession()->remove('stripe_participation_data');

        $this->addFlash('error', 'Le paiement a été annulé. Votre participation n\'a pas été enregistrée.');
        return $this->redirectToRoute('app_evenement_index');
    }

    // ─── FRONT PARTICIPATION UPDATE ────────────────────────

    #[Route('/participation/{id}/modifier', name: 'participation_update', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function participationUpdate(int $id, Request $request, ParticipationRepository $participationRepo, ManagerRegistry $m, ParticipationService $service, EvenementRepository $evenementRepo): Response
    {
        $isAjax = $request->headers->get('X-Requested-With') === 'XMLHttpRequest';
        $participation = $participationRepo->find($id);

        if (!$participation) {
            if ($isAjax) {
                return $this->json(['success' => false, 'errors' => ['general' => 'Participation introuvable.']]);
            }
            $this->addFlash('error', 'Participation introuvable.');
            return $this->redirectToRoute('app_evenement_index');
        }

        $today = (new \DateTime('today'))->format('Y-m-d');

        // Keep the existing statut — front office cannot change it
        $currentStatut = $participation->getStatut();

        $data = [
            'id_evenement'       => $request->request->get('id_evenement', ''),
            'date_participation' => $today,
            'nbr_participation'  => $request->request->get('nbr_participation', ''),
            'statut'             => $currentStatut,
            'mode_paiement'      => $request->request->get('mode_paiement', ''),
        ];

        // Check that today is on or before the event date
        $evenement = $evenementRepo->find((int)$data['id_evenement']);
        if ($evenement && $evenement->getDate()) {
            $eventDate = $evenement->getDate()->format('Y-m-d');
            if ($today > $eventDate) {
                if ($isAjax) {
                    return $this->json(['success' => false, 'errors' => ['date' => 'Vous ne pouvez plus modifier cette participation car la date de l\'événement est dépassée.']]);
                }
                $this->addFlash('error', 'Vous ne pouvez plus modifier cette participation car la date de l\'événement est dépassée.');
                return $this->redirectToRoute('app_evenement_index');
            }
        }

        $errors = $service->validate($data, $participation->getId());

        if (!empty($errors)) {
            if ($isAjax) {
                return $this->json(['success' => false, 'errors' => $errors]);
            }
            $session = $request->getSession();
            $session->set('edit_participation_errors', $errors);
            $session->set('edit_participation_data', $data);
            $session->set('edit_participation_id', $id);
            return $this->redirectToRoute('app_evenement_index');
        }

        $em = $m->getManager();

        $participation->setEvenement($evenement);
        $participation->setDateParticipation(\DateTime::createFromFormat('Y-m-d', $today));
        $participation->setNbrParticipation((int)$data['nbr_participation']);
        $participation->setStatut($currentStatut);

        if ($evenement && $evenement->isPaiement() && !empty($data['mode_paiement'])) {
            $participation->setModePaiement($data['mode_paiement']);
        } else {
            $participation->setModePaiement(null);
        }

        $em->persist($participation);
        $em->flush();

        if ($isAjax) {
            return $this->json(['success' => true, 'redirect' => $this->generateUrl('app_evenement_index')]);
        }
        $this->addFlash('success', 'Participation modifiée avec succès !');
        return $this->redirectToRoute('app_evenement_index');
    }

    // ─── AJAX SEARCH (front-office dynamic search) ─────────

    #[Route('/search', name: 'search', methods: ['GET'])]
    public function search(Request $request, EvenementRepository $repo, ParticipationRepository $participationRepo): JsonResponse
    {
        $q     = trim($request->query->get('q', ''));
        $sort  = $request->query->get('sort', 'date');
        $order = $request->query->get('order', 'ASC');

        $evenements = $repo->searchAndSort($q, '', '', $sort, $order);

        $data = [];
        foreach ($evenements as $evt) {
            $participations = $participationRepo->findByEvenement($evt->getId());
            $partData = [];
            foreach ($participations as $p) {
                $partData[] = [
                    'id'               => $p->getId(),
                    'dateParticipation' => $p->getDateParticipation()?->format('Y-m-d'),
                    'nbrParticipation'  => $p->getNbrParticipation(),
                    'statut'           => $p->getStatut(),
                    'modePaiement'     => $p->getModePaiement(),
                ];
            }

            $data[] = [
                'id'              => $evt->getId(),
                'nom'             => $evt->getNom(),
                'typeEvenement'   => $evt->getTypeEvenement(),
                'nbrParticipant'  => $evt->getNbrParticipant(),
                'date'            => $evt->getDate()?->format('Y-m-d'),
                'dateFmt'         => $evt->getDate()?->format('d M Y'),
                'heure'           => $evt->getHeure()?->format('H\\hi'),
                'lieu'            => $evt->getLieu(),
                'description'     => $evt->getDescription(),
                'paiement'        => $evt->isPaiement(),
                'image'           => $evt->getImage(),
                'participations'  => $partData,
            ];
        }

        return $this->json(['results' => $data, 'count' => count($data)]);
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
