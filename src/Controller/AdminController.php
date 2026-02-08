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
    #[Route('/evenement', name: 'evenements', methods: ['GET'])]
    public function evenements(Request $request, EvenementService $evenementService, EvenementRepository $evenementRepo): Response
    {
        $q        = trim($request->query->get('q', ''));
        $type     = $request->query->get('type', '');
        $paiement = $request->query->get('paiement', '');
        $sort     = $request->query->get('sort', 'date');
        $order    = $request->query->get('order', 'DESC');

        return $this->render('admin/evenements.html.twig', [
            'evenements'      => $evenementRepo->searchAndSort($q, $type, $paiement, $sort, $order),
            'totalCount'      => $evenementService->countAll(),
            'upcomingCount'   => $evenementService->countUpcoming(),
            'totalAttendees'  => $evenementService->sumParticipants(),
            'paidCount'       => $evenementService->countPaid(),
            'q'               => $q,
            'type'            => $type,
            'paiement'        => $paiement,
            'sort'            => $sort,
            'order'           => $order,
        ]);
    }

    /**
     * Store – Create a new event (form POST from admin modal).
     */
    #[Route('/evenement/store', name: 'evenement_store', methods: ['POST'])]
    public function evenementStore(Request $request, ManagerRegistry $m, EvenementService $service): Response
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

        $errors = $service->validate($data);

        if (!empty($errors)) {
            $firstError = reset($errors);
            $this->addFlash('error', $firstError);
            return $this->redirectToRoute('app_admin_evenements');
        }

        $em = $m->getManager();
        $evenement = new Evenement();
        $this->hydrateEvent($evenement, $data);
        $em->persist($evenement);
        $em->flush();

        $this->addFlash('success', 'Événement créé avec succès !');
        return $this->redirectToRoute('app_admin_evenements');
    }

    /**
     * Update – Modify an existing event (form POST from admin modal).
     */
    #[Route('/evenement/{id}/update', name: 'evenement_update', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function evenementUpdate($id, Request $request, EvenementRepository $repo, ManagerRegistry $m, EvenementService $service): Response
    {
        $evenement = $repo->find($id);

        if (!$evenement) {
            $this->addFlash('error', 'Événement introuvable.');
            return $this->redirectToRoute('app_admin_evenements');
        }

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

        $errors = $service->validate($data);

        if (!empty($errors)) {
            $firstError = reset($errors);
            $this->addFlash('error', $firstError);
            return $this->redirectToRoute('app_admin_evenements');
        }

        $em = $m->getManager();
        $this->hydrateEvent($evenement, $data);
        $em->persist($evenement);
        $em->flush();

        $this->addFlash('success', 'Événement modifié avec succès !');
        return $this->redirectToRoute('app_admin_evenements');
    }

    /**
     * Delete – Remove an event (form POST from admin delete confirmation).
     */
    #[Route('/evenement/{id}/delete', name: 'evenement_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function evenementDelete($id, EvenementRepository $repo, ManagerRegistry $m): Response
    {
        $evenement = $repo->find($id);

        if (!$evenement) {
            $this->addFlash('error', 'Événement introuvable.');
            return $this->redirectToRoute('app_admin_evenements');
        }

        $em = $m->getManager();
        $em->remove($evenement);
        $em->flush();

        $this->addFlash('success', 'Événement supprimé avec succès !');
        return $this->redirectToRoute('app_admin_evenements');
    }

    /**
     * Populate an Evenement entity from validated form data.
     */
    private function hydrateEvent(Evenement $evenement, array $data): void
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
            $uploadDir = __DIR__ . '/../../public/uploads/evenements';
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

    /**
     * Participations – Full participation management page (list, add, edit, delete).
     */
    #[Route('/participation', name: 'participations', methods: ['GET'])]
    public function participations(Request $request, ParticipationService $service, ParticipationRepository $repo, EvenementRepository $evenementRepo): Response
    {
        $q        = trim($request->query->get('q', ''));
        $statut   = $request->query->get('statut', '');
        $paiement = $request->query->get('paiement', '');
        $sort     = $request->query->get('sort', 'dateParticipation');
        $order    = $request->query->get('order', 'DESC');

        return $this->render('admin/participations.html.twig', [
            'participations'  => $repo->searchAndSort($q, $statut, $paiement, $sort, $order),
            'evenements'      => $evenementRepo->findBy([], ['date' => 'ASC']),
            'totalCount'      => $service->countAll(),
            'confirmedCount'  => $service->countConfirmed(),
            'pendingCount'    => $service->countPending(),
            'cancelledCount'  => $service->countCancelled(),
            'totalPlaces'     => $service->sumAllPlaces(),
            'q'               => $q,
            'statut'          => $statut,
            'paiement'        => $paiement,
            'sort'            => $sort,
            'order'           => $order,
        ]);
    }

    /**
     * Store – Create a new participation (form POST from admin modal).
     */
    #[Route('/participation/store', name: 'participation_store', methods: ['POST'])]
    public function participationStore(Request $request, ManagerRegistry $m, ParticipationService $service, EvenementRepository $evenementRepo): Response
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
            $firstError = reset($errors);
            $this->addFlash('error', $firstError);
            return $this->redirectToRoute('app_admin_participations');
        }

        $em = $m->getManager();
        $participation = new Participation();
        $this->hydrateParticipation($participation, $data, $evenementRepo);
        $em->persist($participation);
        $em->flush();

        $this->addFlash('success', 'Participation créée avec succès !');
        return $this->redirectToRoute('app_admin_participations');
    }

    /**
     * Update – Modify an existing participation (form POST from admin modal).
     */
    #[Route('/participation/{id}/update', name: 'participation_update', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function participationUpdate($id, Request $request, ParticipationRepository $repo, ManagerRegistry $m, ParticipationService $service, EvenementRepository $evenementRepo): Response
    {
        $participation = $repo->find($id);

        if (!$participation) {
            $this->addFlash('error', 'Participation introuvable.');
            return $this->redirectToRoute('app_admin_participations');
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
            $firstError = reset($errors);
            $this->addFlash('error', $firstError);
            return $this->redirectToRoute('app_admin_participations');
        }

        $em = $m->getManager();
        $this->hydrateParticipation($participation, $data, $evenementRepo);
        $em->persist($participation);
        $em->flush();

        $this->addFlash('success', 'Participation modifiée avec succès !');
        return $this->redirectToRoute('app_admin_participations');
    }

    /**
     * Delete – Remove a participation (form POST from admin delete confirmation).
     */
    #[Route('/participation/{id}/delete', name: 'participation_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function participationDelete($id, ParticipationRepository $repo, ManagerRegistry $m): Response
    {
        $participation = $repo->find($id);

        if (!$participation) {
            $this->addFlash('error', 'Participation introuvable.');
            return $this->redirectToRoute('app_admin_participations');
        }

        $em = $m->getManager();
        $em->remove($participation);
        $em->flush();

        $this->addFlash('success', 'Participation supprimée avec succès !');
        return $this->redirectToRoute('app_admin_participations');
    }

    /**
     * Populate a Participation entity from validated form data.
     */
    private function hydrateParticipation(Participation $participation, array $data, EvenementRepository $evenementRepo): void
    {
        $evenement = $evenementRepo->find((int)$data['id_evenement']);
        $participation->setEvenement($evenement);

        $dateObj = \DateTime::createFromFormat('Y-m-d', $data['date_participation']);
        $participation->setDateParticipation($dateObj);

        $participation->setNbrParticipation((int)$data['nbr_participation']);
        $participation->setStatut($data['statut']);

        if ($evenement && $evenement->isPaiement() && !empty($data['mode_paiement'])) {
            $participation->setModePaiement($data['mode_paiement']);
        } else {
            $participation->setModePaiement(null);
        }
    }

    /**
     * Galeries – Full gallery management page (list, add, edit, delete).
     */
    #[Route('/galerie', name: 'galeries')]
    public function galeries(): Response
    {
        return $this->render('admin/galeries.html.twig');
    }

    /**
     * Œuvres – Full artwork management page (list, add, edit, delete).
     */
    #[Route('/oeuvre', name: 'oeuvres')]
    public function oeuvres(): Response
    {
        return $this->render('admin/oeuvres.html.twig');
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
     * Évaluations – Full evaluation management page (list, add, edit, delete).....
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
