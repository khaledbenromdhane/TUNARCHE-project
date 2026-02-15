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
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use Dompdf\Dompdf;
use Dompdf\Options;

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
            'prix'             => $request->request->get('prix', ''),
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
            'prix'             => $request->request->get('prix', ''),
        ];

        $imageFile = $request->files->get('image');
        if ($imageFile) {
            $data['image_file'] = $imageFile;
        }

        $errors = $service->validate($data, true);

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

        // Set price only for paid events
        if (!empty($data['paiement']) && $data['prix'] !== '') {
            $evenement->setPrix((float)$data['prix']);
        } else {
            $evenement->setPrix(null);
        }

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

        $errors = $service->validate($data, $participation->getId(), true);

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

    // ─── AJAX SEARCH: Evenements ───────────────────────────

    #[Route('/evenement/search', name: 'evenement_search', methods: ['GET'])]
    public function evenementSearch(Request $request, EvenementRepository $repo): JsonResponse
    {
        $q        = trim($request->query->get('q', ''));
        $type     = $request->query->get('type', '');
        $paiement = $request->query->get('paiement', '');
        $sort     = $request->query->get('sort', 'date');
        $order    = $request->query->get('order', 'DESC');

        $evenements = $repo->searchAndSort($q, $type, $paiement, $sort, $order);

        $data = [];
        foreach ($evenements as $evt) {
            $data[] = [
                'id'              => $evt->getId(),
                'nom'             => $evt->getNom(),
                'typeEvenement'   => $evt->getTypeEvenement(),
                'nbrParticipant'  => $evt->getNbrParticipant(),
                'date'            => $evt->getDate()?->format('Y-m-d'),
                'dateFmt'         => $evt->getDate()?->format('M d, Y'),
                'heure'           => $evt->getHeure()?->format('H:i'),
                'lieu'            => $evt->getLieu(),
                'description'     => $evt->getDescription(),
                'paiement'        => $evt->isPaiement(),
                'image'           => $evt->getImage(),
            ];
        }

        return $this->json(['results' => $data, 'count' => count($data)]);
    }

    // ─── AJAX SEARCH: Participations ───────────────────────

    #[Route('/participation/search', name: 'participation_search', methods: ['GET'])]
    public function participationSearch(Request $request, ParticipationRepository $repo): JsonResponse
    {
        $q        = trim($request->query->get('q', ''));
        $statut   = $request->query->get('statut', '');
        $paiement = $request->query->get('paiement', '');
        $sort     = $request->query->get('sort', 'dateParticipation');
        $order    = $request->query->get('order', 'DESC');

        $participations = $repo->searchAndSort($q, $statut, $paiement, $sort, $order);

        $data = [];
        foreach ($participations as $p) {
            $evt = $p->getEvenement();
            $data[] = [
                'id'               => $p->getId(),
                'idEvenement'      => $evt?->getId(),
                'eventName'        => $evt?->getNom() ?? 'N/A',
                'eventPaid'        => $evt ? $evt->isPaiement() : false,
                'eventDate'        => $evt?->getDate()?->format('Y-m-d'),
                'eventLieu'        => $evt?->getLieu(),
                'eventType'        => $evt?->getTypeEvenement(),
                'dateParticipation'=> $p->getDateParticipation()?->format('Y-m-d'),
                'dateFmt'          => $p->getDateParticipation()?->format('d/m/Y'),
                'statut'           => $p->getStatut(),
                'nbrParticipation' => $p->getNbrParticipation(),
                'modePaiement'     => $p->getModePaiement(),
            ];
        }

        return $this->json(['results' => $data, 'count' => count($data)]);
    }

    // ─── PDF EXPORT: Participations ────────────────────────

    #[Route('/participation/export', name: 'participation_export', methods: ['GET'])]
    public function participationExport(ParticipationRepository $repo): Response
    {
        $participations = $repo->searchAndSort('', '', '', 'dateParticipation', 'DESC');

        // Configure Dompdf
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');
        
        $dompdf = new Dompdf($options);

        // Generate HTML content
        $html = $this->generateParticipationsPdfHtml($participations);
        
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        // Output PDF
        return new Response(
            $dompdf->output(),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="participations_export_' . date('Y-m-d') . '.pdf"',
            ]
        );
    }

    // ─── PDF EXPORT: Evenements ─────────────────────────────

    #[Route('/evenement/export', name: 'evenement_export', methods: ['GET'])]
    public function evenementExport(EvenementRepository $repo): Response
    {
        $evenements = $repo->searchAndSort('', '', '', 'date', 'ASC');

        // Configure Dompdf
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');
        
        $dompdf = new Dompdf($options);

        // Generate HTML content
        $html = $this->generateEvenementsPdfHtml($evenements);
        
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        // Output PDF
        return new Response(
            $dompdf->output(),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="evenements_export_' . date('Y-m-d') . '.pdf"',
            ]
        );
    }

    // ─── HELPER: Generate Participations PDF HTML ──────────

    private function generateParticipationsPdfHtml(array $participations): string
    {
        $totalCount = count($participations);
        $totalPlaces = array_sum(array_map(fn($p) => $p->getNbrParticipation(), $participations));
        $currentDate = date('d/m/Y H:i');

        $rows = '';
        $i = 0;
        foreach ($participations as $p) {
            $evt = $p->getEvenement();
            $bgColor = ($i % 2 === 0) ? '#ffffff' : '#f4f6f9';

            $statut = $p->getStatut();
            if ($statut === 'Confirmée') {
                $statutHtml = '<span style="background-color:#d4edda;color:#155724;padding:2px 8px;border-radius:3px;font-size:7pt;font-weight:bold;">Confirmee</span>';
            } elseif ($statut === 'En attente') {
                $statutHtml = '<span style="background-color:#fff3cd;color:#856404;padding:2px 8px;border-radius:3px;font-size:7pt;font-weight:bold;">En attente</span>';
            } else {
                $statutHtml = '<span style="background-color:#f8d7da;color:#721c24;padding:2px 8px;border-radius:3px;font-size:7pt;font-weight:bold;">Annulee</span>';
            }

            $mode = $p->getModePaiement();
            if ($mode === 'Carte') {
                $paiementHtml = '<span style="background-color:#d1ecf1;color:#0c5460;padding:2px 8px;border-radius:3px;font-size:7pt;font-weight:bold;">Carte</span>';
            } elseif ($mode === 'Cash') {
                $paiementHtml = '<span style="background-color:#e2e3e5;color:#383d41;padding:2px 8px;border-radius:3px;font-size:7pt;font-weight:bold;">Cash</span>';
            } else {
                $paiementHtml = '<span style="background-color:#d6d8db;color:#1b1e21;padding:2px 8px;border-radius:3px;font-size:7pt;font-weight:bold;">Gratuit</span>';
            }

            $rows .= '
            <tr style="background-color:' . $bgColor . ';">
                <td style="border:1px solid #dee2e6;padding:6px 8px;text-align:center;font-weight:bold;color:#b8860b;font-size:7.5pt;">' . $p->getId() . '</td>
                <td style="border:1px solid #dee2e6;padding:6px 8px;font-weight:bold;color:#1a1a2e;font-size:7.5pt;">' . htmlspecialchars($evt?->getNom() ?? 'N/A') . '</td>
                <td style="border:1px solid #dee2e6;padding:6px 8px;font-size:7.5pt;">' . htmlspecialchars($evt?->getTypeEvenement() ?? '') . '</td>
                <td style="border:1px solid #dee2e6;padding:6px 8px;font-size:7.5pt;">' . ($evt?->getDate()?->format('d/m/Y') ?? '') . '</td>
                <td style="border:1px solid #dee2e6;padding:6px 8px;font-size:7.5pt;">' . ($p->getDateParticipation()?->format('d/m/Y') ?? '') . '</td>
                <td style="border:1px solid #dee2e6;padding:6px 8px;text-align:center;font-weight:bold;font-size:7.5pt;">' . $p->getNbrParticipation() . '</td>
                <td style="border:1px solid #dee2e6;padding:6px 8px;text-align:center;font-size:7.5pt;">' . $statutHtml . '</td>
                <td style="border:1px solid #dee2e6;padding:6px 8px;text-align:center;font-size:7.5pt;">' . $paiementHtml . '</td>
                <td style="border:1px solid #dee2e6;padding:6px 8px;font-size:7pt;">' . htmlspecialchars($evt?->getLieu() ?? '') . '</td>
            </tr>';
            $i++;
        }

        $html = '<!DOCTYPE html>
<html>
<head><meta charset="UTF-8">
<style>
    @page { margin: 12mm 10mm; size: A4 landscape; }
    body { font-family: "DejaVu Sans", sans-serif; font-size: 8pt; color: #333; margin: 0; padding: 0; }
    h1 { font-size: 18pt; color: #b8860b; margin: 0; }
    p { margin: 2px 0; }
    table { width: 100%; border-collapse: collapse; }
    .hdr-cell { background-color: #1a1a2e; color: #ffffff; padding: 7px 8px; font-size: 7.5pt; font-weight: bold; text-transform: uppercase; border: 1px solid #1a1a2e; text-align: left; }
</style>
</head>
<body>

<table style="width:100%;margin-bottom:8px;">
  <tr>
    <td style="background-color:#1a1a2e;padding:14px 20px;border-bottom:3px solid #b8860b;">
      <h1 style="color:#b8860b;font-size:18pt;margin:0;">RAPPORT DES PARTICIPATIONS</h1>
      <p style="color:#cccccc;font-size:9pt;margin:4px 0 0 0;">Genere le ' . $currentDate . '</p>
    </td>
  </tr>
</table>

<table style="width:100%;margin-bottom:10px;">
  <tr>
    <td style="background-color:#f8f9fa;border-left:3px solid #b8860b;padding:8px 20px;">
      <span style="color:#666;font-size:7pt;text-transform:uppercase;">TOTAL: </span>
      <span style="color:#b8860b;font-size:13pt;font-weight:bold;">' . $totalCount . '</span>
      <span style="margin-left:30px;color:#666;font-size:7pt;text-transform:uppercase;">PLACES RESERVEES: </span>
      <span style="color:#b8860b;font-size:13pt;font-weight:bold;">' . $totalPlaces . '</span>
    </td>
  </tr>
</table>

<table style="width:100%;border-collapse:collapse;">
  <tr>
    <td class="hdr-cell" style="width:5%;">ID</td>
    <td class="hdr-cell" style="width:18%;">Evenement</td>
    <td class="hdr-cell" style="width:12%;">Type</td>
    <td class="hdr-cell" style="width:10%;">Date Evt</td>
    <td class="hdr-cell" style="width:10%;">Date Part</td>
    <td class="hdr-cell" style="width:7%;text-align:center;">Places</td>
    <td class="hdr-cell" style="width:12%;text-align:center;">Statut</td>
    <td class="hdr-cell" style="width:11%;text-align:center;">Paiement</td>
    <td class="hdr-cell" style="width:15%;">Lieu</td>
  </tr>
  ' . $rows . '
</table>

<table style="width:100%;margin-top:10px;">
  <tr>
    <td style="background-color:#1a1a2e;color:#b8860b;padding:8px 20px;font-size:7pt;border-top:2px solid #b8860b;">
      Artvista - Systeme de gestion des evenements
    </td>
  </tr>
</table>

</body>
</html>';

        return $html;
    }

    // ─── HELPER: Generate Evenements PDF HTML ──────────────

    private function generateEvenementsPdfHtml(array $evenements): string
    {
        $totalCount = count($evenements);
        $totalParticipants = array_sum(array_map(fn($e) => $e->getNbrParticipant(), $evenements));
        $paidCount = count(array_filter($evenements, fn($e) => $e->isPaiement()));
        $currentDate = date('d/m/Y H:i');

        $rows = '';
        $i = 0;
        $today = new \DateTime('today');
        foreach ($evenements as $evt) {
            $bgColor = ($i % 2 === 0) ? '#ffffff' : '#f4f6f9';

            if ($evt->isPaiement()) {
                $paiementHtml = '<span style="background-color:#d4edda;color:#155724;padding:2px 8px;border-radius:3px;font-size:7pt;font-weight:bold;">Payant</span>';
            } else {
                $paiementHtml = '<span style="background-color:#f8d7da;color:#721c24;padding:2px 8px;border-radius:3px;font-size:7pt;font-weight:bold;">Gratuit</span>';
            }

            $isUpcoming = $evt->getDate() >= $today;
            if ($isUpcoming) {
                $statutHtml = '<span style="background-color:#d4edda;color:#155724;padding:2px 8px;border-radius:3px;font-size:7pt;font-weight:bold;">A venir</span>';
            } else {
                $statutHtml = '<span style="background-color:#e2e3e5;color:#383d41;padding:2px 8px;border-radius:3px;font-size:7pt;font-weight:bold;">Passe</span>';
            }

            $rows .= '
            <tr style="background-color:' . $bgColor . ';">
                <td style="border:1px solid #dee2e6;padding:6px 8px;text-align:center;font-weight:bold;color:#b8860b;font-size:7.5pt;">' . $evt->getId() . '</td>
                <td style="border:1px solid #dee2e6;padding:6px 8px;font-weight:bold;color:#1a1a2e;font-size:7.5pt;">' . htmlspecialchars($evt->getNom()) . '</td>
                <td style="border:1px solid #dee2e6;padding:6px 8px;font-size:7.5pt;">' . htmlspecialchars($evt->getTypeEvenement()) . '</td>
                <td style="border:1px solid #dee2e6;padding:6px 8px;text-align:center;font-weight:bold;font-size:7.5pt;">' . $evt->getNbrParticipant() . '</td>
                <td style="border:1px solid #dee2e6;padding:6px 8px;font-size:7.5pt;">' . ($evt->getDate()?->format('d/m/Y') ?? '') . '</td>
                <td style="border:1px solid #dee2e6;padding:6px 8px;font-size:7.5pt;">' . ($evt->getHeure()?->format('H:i') ?? '') . '</td>
                <td style="border:1px solid #dee2e6;padding:6px 8px;font-size:7pt;">' . htmlspecialchars($evt->getLieu()) . '</td>
                <td style="border:1px solid #dee2e6;padding:6px 8px;text-align:center;font-size:7.5pt;">' . $paiementHtml . '</td>
                <td style="border:1px solid #dee2e6;padding:6px 8px;text-align:center;font-size:7.5pt;">' . $statutHtml . '</td>
            </tr>';
            $i++;
        }

        $html = '<!DOCTYPE html>
<html>
<head><meta charset="UTF-8">
<style>
    @page { margin: 12mm 10mm; size: A4 landscape; }
    body { font-family: "DejaVu Sans", sans-serif; font-size: 8pt; color: #333; margin: 0; padding: 0; }
    h1 { font-size: 18pt; color: #b8860b; margin: 0; }
    p { margin: 2px 0; }
    table { width: 100%; border-collapse: collapse; }
    .hdr-cell { background-color: #1a1a2e; color: #ffffff; padding: 7px 8px; font-size: 7.5pt; font-weight: bold; text-transform: uppercase; border: 1px solid #1a1a2e; text-align: left; }
</style>
</head>
<body>

<table style="width:100%;margin-bottom:8px;">
  <tr>
    <td style="background-color:#1a1a2e;padding:14px 20px;border-bottom:3px solid #b8860b;">
      <h1 style="color:#b8860b;font-size:18pt;margin:0;">RAPPORT DES EVENEMENTS</h1>
      <p style="color:#cccccc;font-size:9pt;margin:4px 0 0 0;">Genere le ' . $currentDate . '</p>
    </td>
  </tr>
</table>

<table style="width:100%;margin-bottom:10px;">
  <tr>
    <td style="background-color:#f8f9fa;border-left:3px solid #b8860b;padding:8px 20px;">
      <span style="color:#666;font-size:7pt;text-transform:uppercase;">TOTAL: </span>
      <span style="color:#b8860b;font-size:13pt;font-weight:bold;">' . $totalCount . '</span>
      <span style="margin-left:25px;color:#666;font-size:7pt;text-transform:uppercase;">PLACES: </span>
      <span style="color:#b8860b;font-size:13pt;font-weight:bold;">' . $totalParticipants . '</span>
      <span style="margin-left:25px;color:#666;font-size:7pt;text-transform:uppercase;">PAYANTS: </span>
      <span style="color:#b8860b;font-size:13pt;font-weight:bold;">' . $paidCount . '</span>
    </td>
  </tr>
</table>

<table style="width:100%;border-collapse:collapse;">
  <tr>
    <td class="hdr-cell" style="width:5%;">ID</td>
    <td class="hdr-cell" style="width:22%;">Nom</td>
    <td class="hdr-cell" style="width:12%;">Type</td>
    <td class="hdr-cell" style="width:7%;text-align:center;">Places</td>
    <td class="hdr-cell" style="width:10%;">Date</td>
    <td class="hdr-cell" style="width:7%;">Heure</td>
    <td class="hdr-cell" style="width:18%;">Lieu</td>
    <td class="hdr-cell" style="width:10%;text-align:center;">Paiement</td>
    <td class="hdr-cell" style="width:9%;text-align:center;">Statut</td>
  </tr>
  ' . $rows . '
</table>

<table style="width:100%;margin-top:10px;">
  <tr>
    <td style="background-color:#1a1a2e;color:#b8860b;padding:8px 20px;font-size:7pt;border-top:2px solid #b8860b;">
      Artvista - Systeme de gestion des evenements
    </td>
  </tr>
</table>

</body>
</html>';

        return $html;
    }
}
