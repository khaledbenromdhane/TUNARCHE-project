<?php

namespace App\Controller;

use App\Entity\Participation;
use App\Repository\EvenementRepository;
use App\Repository\ParticipationRepository;
use App\Service\ParticipationService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * ParticipationController – Front-end CRUD for Participation entity.
 * Uses ManagerRegistry for persistence and ParticipationRepository for queries.
 * Form-based operations only (no JSON/AJAX).
 */
#[Route('/participation', name: 'app_participation_')]
class ParticipationController extends AbstractController
{
    // ─── LIST ──────────────────────────────────────────────

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request, ParticipationRepository $repo): Response
    {
        $q     = trim($request->query->get('q', ''));
        $sort  = $request->query->get('sort', 'dateParticipation');
        $order = $request->query->get('order', 'DESC');

        $participations = $repo->searchAndSort($q, '', '', $sort, $order);

        return $this->render('participation/index.html.twig', [
            'participations' => $participations,
            'q'              => $q,
            'sort'           => $sort,
            'order'          => $order,
        ]);
    }

    // ─── CREATE ────────────────────────────────────────────

    #[Route('/ajouter', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, ManagerRegistry $m, ParticipationService $service, EvenementRepository $evenementRepo): Response
    {
        $errors = [];
        $data   = [];

        if ($request->isMethod('POST')) {
            $data   = $this->extractFormData($request);
            $errors = $service->validate($data);

            if (empty($errors)) {
                $em = $m->getManager();
                $participation = new Participation();
                $this->hydrate($participation, $data, $evenementRepo);
                $em->persist($participation);
                $em->flush();

                $this->addFlash('success', 'Participation créée avec succès !');
                return $this->redirectToRoute('app_participation_index');
            }
        }

        // Pre-select event if passed via query string
        $preselectedEvent = $request->query->get('evenement');

        return $this->render('participation/new.html.twig', [
            'errors'      => $errors,
            'data'        => $data,
            'evenements'  => $evenementRepo->findBy([], ['date' => 'ASC']),
            'preselected' => $preselectedEvent,
        ]);
    }

    // ─── SHOW ──────────────────────────────────────────────

    #[Route('/{id}', name: 'show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show($id, ParticipationRepository $repo): Response
    {
        $participation = $repo->find($id);

        if (!$participation) {
            $this->addFlash('error', 'Participation introuvable.');
            return $this->redirectToRoute('app_participation_index');
        }

        return $this->render('participation/show.html.twig', [
            'participation' => $participation,
        ]);
    }

    // ─── EDIT ──────────────────────────────────────────────

    #[Route('/{id}/modifier', name: 'edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit($id, Request $request, ParticipationRepository $repo, ManagerRegistry $m, ParticipationService $service, EvenementRepository $evenementRepo): Response
    {
        $participation = $repo->find($id);

        if (!$participation) {
            $this->addFlash('error', 'Participation introuvable.');
            return $this->redirectToRoute('app_participation_index');
        }

        $errors = [];

        if ($request->isMethod('POST')) {
            $data   = $this->extractFormData($request);
            $errors = $service->validate($data, $participation->getId());

            if (empty($errors)) {
                $em = $m->getManager();
                $this->hydrate($participation, $data, $evenementRepo);
                $em->persist($participation);
                $em->flush();

                $this->addFlash('success', 'Participation modifiée avec succès !');
                return $this->redirectToRoute('app_participation_index');
            }
        }

        return $this->render('participation/edit.html.twig', [
            'participation' => $participation,
            'errors'        => $errors,
            'evenements'    => $evenementRepo->findBy([], ['date' => 'ASC']),
        ]);
    }

    // ─── DELETE ────────────────────────────────────────────

    #[Route('/{id}/supprimer', name: 'delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete($id, Request $request, ParticipationRepository $repo, ManagerRegistry $m): Response
    {
        $participation = $repo->find($id);

        if (!$participation) {
            $this->addFlash('error', 'Participation introuvable.');
            return $this->redirectToRoute('app_participation_index');
        }

        $token = $request->request->get('_token', '');
        if (!$this->isCsrfTokenValid('delete_participation_' . $id, $token)) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_participation_index');
        }

        $em = $m->getManager();
        $em->remove($participation);
        $em->flush();

        $this->addFlash('success', 'Participation supprimée avec succès !');
        return $this->redirectToRoute('app_participation_index');
    }

    // ─── HELPERS ───────────────────────────────────────────

    private function extractFormData(Request $request): array
    {
        return [
            'id_evenement'       => $request->request->get('id_evenement', ''),
            'date_participation' => $request->request->get('date_participation', ''),
            'nbr_participation'  => $request->request->get('nbr_participation', ''),
            'statut'             => $request->request->get('statut', ''),
            'mode_paiement'      => $request->request->get('mode_paiement', ''),
        ];
    }

    private function hydrate(Participation $participation, array $data, EvenementRepository $evenementRepo): void
    {
        $evenement = $evenementRepo->find((int)$data['id_evenement']);
        $participation->setEvenement($evenement);

        $dateObj = \DateTime::createFromFormat('Y-m-d', $data['date_participation']);
        $participation->setDateParticipation($dateObj);

        $participation->setNbrParticipation((int)$data['nbr_participation']);
        $participation->setStatut($data['statut']);

        // mode_paiement only if event is paid
        if ($evenement && $evenement->isPaiement() && !empty($data['mode_paiement'])) {
            $participation->setModePaiement($data['mode_paiement']);
        } else {
            $participation->setModePaiement(null);
        }

        // id_user will be set when User entity is integrated
        // For now, leave null or set a dummy value
    }
}
