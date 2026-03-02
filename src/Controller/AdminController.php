<?php

namespace App\Controller;

use App\Entity\Evenement;
use App\Entity\Participation;
use App\Entity\User;
use App\Entity\Blog\Commentaire;
use App\Entity\Blog\Publication;
use App\Repository\EvenementRepository;
use App\Repository\ParticipationRepository;
use App\Repository\Blog\CommentaireRepository;
use App\Repository\Blog\PublicationRepository;
use App\Repository\UserRepository;
use App\Service\EvenementService;
use App\Service\ParticipationService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\String\Slugger\AsciiSlugger;
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
    public function users(UserRepository $userRepo): Response
    {
        $users = $userRepo->findBy([], ['id' => 'ASC']);

        $totalCount  = count($users);
        $adminCount  = 0;
        $artistCount = 0;
        $participantCount = 0;

        foreach ($users as $u) {
            $roles = $u->getRole();
            if (in_array('ROLE_ADMIN', $roles)) {
                $adminCount++;
            } elseif (in_array('ROLE_ARTIST', $roles)) {
                $artistCount++;
            } else {
                $participantCount++;
            }
        }

        return $this->render('admin/users.html.twig', [
            'users'            => $users,
            'totalCount'       => $totalCount,
            'adminCount'       => $adminCount,
            'artistCount'      => $artistCount,
            'participantCount' => $participantCount,
        ]);
    }

    // ─── User AJAX Search ─────────────────────────────────────
    #[Route('/user/search', name: 'user_search', methods: ['GET'])]
    public function userSearch(Request $request, UserRepository $userRepo): JsonResponse
    {
        $q    = trim($request->query->get('q', ''));
        $role = trim($request->query->get('role', ''));

        $qb = $userRepo->createQueryBuilder('u');

        if ($q !== '') {
            $qb->andWhere('u.nom LIKE :q OR u.prenom LIKE :q OR u.email LIKE :q OR u.telephone LIKE :q')
               ->setParameter('q', '%' . $q . '%');
        }
        if ($role !== '') {
            $qb->andWhere('u.role LIKE :role')
               ->setParameter('role', '%' . $role . '%');
        }

        $qb->orderBy('u.id', 'ASC');
        $users = $qb->getQuery()->getResult();

        $data = array_map(fn($u) => $this->buildUserData($u), $users);
        return new JsonResponse(['users' => $data]);
    }

    // ─── User AJAX Create ─────────────────────────────────────
    #[Route('/user/create', name: 'user_create', methods: ['POST'])]
    public function userCreate(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher
    ): JsonResponse {
        $nom       = trim($request->request->get('nom', ''));
        $prenom    = trim($request->request->get('prenom', ''));
        $email     = trim($request->request->get('email', ''));
        $telephone = trim($request->request->get('telephone', ''));
        $password  = $request->request->get('password', '');
        $role      = trim($request->request->get('role', 'ROLE_USER'));

        // Basic validation
        if ($nom === '' || $prenom === '' || $email === '' || $password === '') {
            return new JsonResponse(['error' => 'Nom, prénom, email et mot de passe sont obligatoires.'], 400);
        }
        if (strlen($password) < 6) {
            return new JsonResponse(['error' => 'Le mot de passe doit contenir au moins 6 caractères.'], 400);
        }

        // Check duplicate email
        $existing = $em->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($existing) {
            return new JsonResponse(['error' => 'Cet email est déjà utilisé.'], 400);
        }

        $user = new User();
        $user->setNom($nom);
        $user->setPrenom($prenom);
        $user->setEmail($email);
        $user->setTelephone($telephone);
        $user->setRole($role);
        $user->setPassword($passwordHasher->hashPassword($user, $password));

        $em->persist($user);
        $em->flush();

        return new JsonResponse(['success' => true, 'user' => $this->buildUserData($user)]);
    }

    // ─── User AJAX Update ─────────────────────────────────────
    #[Route('/user/update/{id}', name: 'user_update', methods: ['POST'])]
    public function userUpdate(
        int $id,
        Request $request,
        EntityManagerInterface $em,
        UserRepository $userRepo,
        UserPasswordHasherInterface $passwordHasher
    ): JsonResponse {
        $user = $userRepo->find($id);
        if (!$user) {
            return new JsonResponse(['error' => 'Utilisateur introuvable.'], 404);
        }

        $nom       = trim($request->request->get('nom', ''));
        $prenom    = trim($request->request->get('prenom', ''));
        $email     = trim($request->request->get('email', ''));
        $telephone = trim($request->request->get('telephone', ''));
        $password  = $request->request->get('password', '');
        $role      = trim($request->request->get('role', ''));

        if ($nom !== '') $user->setNom($nom);
        if ($prenom !== '') $user->setPrenom($prenom);
        if ($email !== '') {
            // Check duplicate email (different user)
            $existing = $em->getRepository(User::class)->findOneBy(['email' => $email]);
            if ($existing && $existing->getId() !== $user->getId()) {
                return new JsonResponse(['error' => 'Cet email est déjà utilisé par un autre utilisateur.'], 400);
            }
            $user->setEmail($email);
        }
        if ($telephone !== '') $user->setTelephone($telephone);
        if ($role !== '') $user->setRole($role);
        if ($password !== '') {
            if (strlen($password) < 6) {
                return new JsonResponse(['error' => 'Le mot de passe doit contenir au moins 6 caractères.'], 400);
            }
            $user->setPassword($passwordHasher->hashPassword($user, $password));
        }

        $em->flush();
        return new JsonResponse(['success' => true, 'user' => $this->buildUserData($user)]);
    }

    // ─── User AJAX Delete ─────────────────────────────────────
    #[Route('/user/delete/{id}', name: 'user_delete', methods: ['POST'])]
    public function userDelete(int $id, EntityManagerInterface $em, UserRepository $userRepo): JsonResponse
    {
        $user = $userRepo->find($id);
        if (!$user) {
            return new JsonResponse(['error' => 'Utilisateur introuvable.'], 404);
        }
        $em->remove($user);
        $em->flush();
        return new JsonResponse(['success' => true]);
    }

    // ─── User AJAX Get single ─────────────────────────────────
    #[Route('/user/get/{id}', name: 'user_get', methods: ['GET'])]
    public function userGet(int $id, UserRepository $userRepo): JsonResponse
    {
        $user = $userRepo->find($id);
        if (!$user) {
            return new JsonResponse(['error' => 'Utilisateur introuvable.'], 404);
        }
        return new JsonResponse(['user' => $this->buildUserData($user)]);
    }

    // ─── Helper: build user data array ────────────────────────
    private function buildUserData(User $user): array
    {
        $roles = $user->getRole();
        $roleLabel = 'participant';
        if (in_array('ROLE_ADMIN', $roles)) {
            $roleLabel = 'admin';
        } elseif (in_array('ROLE_ARTIST', $roles)) {
            $roleLabel = 'artiste';
        }

        return [
            'id'        => $user->getId(),
            'nom'       => $user->getNom(),
            'prenom'    => $user->getPrenom(),
            'email'     => $user->getEmail(),
            'telephone' => $user->getTelephone(),
            'role'      => $roleLabel,
            'roleRaw'   => $roles,
            'avatar'    => $user->getAvatarUrl(),
        ];
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
    public function commentaires(CommentaireRepository $comRepo, PublicationRepository $pubRepo, UserRepository $userRepo): Response
    {
        $commentaires = $comRepo->findBy([], ['dateCreation' => 'DESC']);
        $total = count($commentaires);
        $visible = count(array_filter($commentaires, fn(Commentaire $c) => $c->getStatus() === 'visible'));
        $totalLikes = array_sum(array_map(fn(Commentaire $c) => $c->getNbLikes() ?? 0, $commentaires));
        $reported = count(array_filter($commentaires, fn(Commentaire $c) => $c->isEstSignale()));

        $publications = $pubRepo->findAll();
        $users = $userRepo->findAll();

        return $this->render('admin/commentaires.html.twig', [
            'commentaires' => $commentaires,
            'totalCount' => $total,
            'visibleCount' => $visible,
            'totalLikes' => $totalLikes,
            'reportedCount' => $reported,
            'publications' => $publications,
            'users' => $users,
        ]);
    }

    // ─── AJAX: Commentaire CRUD ──────────────────────────────

    #[Route('/commentaire/search', name: 'commentaire_search', methods: ['GET'])]
    public function commentaireSearch(Request $request, CommentaireRepository $repo): JsonResponse
    {
        $q = trim($request->query->get('q', ''));
        $status = $request->query->get('status', '');
        $signale = $request->query->get('signale', '');
        $sort = $request->query->get('sort', 'date');
        $order = strtoupper($request->query->get('order', 'DESC'));
        if (!in_array($order, ['ASC', 'DESC'])) $order = 'DESC';

        $qb = $repo->createQueryBuilder('c')
            ->leftJoin('c.user', 'u')
            ->leftJoin('c.publication', 'p');

        if ($q !== '') {
            $qb->andWhere('c.content LIKE :q OR u.nom LIKE :q OR u.prenom LIKE :q OR p.titre LIKE :q')
               ->setParameter('q', '%' . $q . '%');
        }
        if ($status !== '') {
            $qb->andWhere('c.status = :status')->setParameter('status', $status);
        }
        if ($signale !== '') {
            $qb->andWhere('c.estSignale = :signale')->setParameter('signale', $signale === 'true');
        }

        $sortField = match ($sort) {
            'id' => 'c.idCommentaire',
            'status' => 'c.status',
            'likes' => 'c.nbLikes',
            default => 'c.dateCreation',
        };
        $qb->orderBy($sortField, $order);
        $commentaires = $qb->getQuery()->getResult();

        $data = [];
        foreach ($commentaires as $com) {
            $data[] = $this->buildCommentaireData($com);
        }

        return $this->json(['results' => $data, 'count' => count($data)]);
    }

    #[Route('/commentaire/create', name: 'commentaire_create', methods: ['POST'])]
    public function commentaireCreate(Request $request, EntityManagerInterface $em, UserRepository $userRepo, PublicationRepository $pubRepo): JsonResponse
    {
        $content = strip_tags(trim($request->request->get('content', '')));
        $userId = $request->request->get('userId', '');
        $publicationId = $request->request->get('publicationId', '');
        $status = $request->request->get('status', 'visible');
        $nbLikes = (int) $request->request->get('nbLikes', 0);
        $parentId = (int) $request->request->get('parentId', 0);
        $estSignale = $request->request->get('estSignale', '0') === '1';
        $raisonSignalement = strip_tags(trim($request->request->get('raisonSignalement', '')));

        if (empty($content) || strlen($content) < 3) {
            return $this->json(['success' => false, 'message' => 'Comment text must be at least 3 characters.'], 400);
        }
        if (empty($publicationId)) {
            return $this->json(['success' => false, 'message' => 'Publication is required.'], 400);
        }

        $publication = $pubRepo->find($publicationId);
        if (!$publication) {
            return $this->json(['success' => false, 'message' => 'Publication not found.'], 404);
        }

        $user = null;
        if (!empty($userId)) {
            $user = $userRepo->find($userId);
        }

        $commentaire = new Commentaire();
        $commentaire->setContent($content);
        $commentaire->setUser($user);
        $commentaire->setPublication($publication);
        $commentaire->setStatus($status);
        $commentaire->setNbLikes($nbLikes);
        $commentaire->setNbDislikes(0);
        $commentaire->setParentId($parentId);
        $commentaire->setEstSignale($estSignale);
        $commentaire->setRaisonSignalement($estSignale ? $raisonSignalement : null);
        $commentaire->setDateCreation(new \DateTime());

        $em->persist($commentaire);
        $em->flush();

        return $this->json(['success' => true, 'message' => 'Commentaire created successfully!', 'commentaire' => $this->buildCommentaireData($commentaire)]);
    }

    #[Route('/commentaire/{id}/update', name: 'commentaire_update', methods: ['POST'])]
    public function commentaireUpdate(int $id, Request $request, EntityManagerInterface $em, CommentaireRepository $repo, UserRepository $userRepo, PublicationRepository $pubRepo): JsonResponse
    {
        $commentaire = $repo->find($id);
        if (!$commentaire) {
            return $this->json(['success' => false, 'message' => 'Commentaire not found.'], 404);
        }

        $content = strip_tags(trim($request->request->get('content', '')));
        $userId = $request->request->get('userId', '');
        $publicationId = $request->request->get('publicationId', '');
        $status = $request->request->get('status', 'visible');
        $nbLikes = (int) $request->request->get('nbLikes', 0);
        $parentId = (int) $request->request->get('parentId', 0);
        $estSignale = $request->request->get('estSignale', '0') === '1';
        $raisonSignalement = strip_tags(trim($request->request->get('raisonSignalement', '')));

        if (empty($content) || strlen($content) < 3) {
            return $this->json(['success' => false, 'message' => 'Comment text must be at least 3 characters.'], 400);
        }
        if (empty($publicationId)) {
            return $this->json(['success' => false, 'message' => 'Publication is required.'], 400);
        }

        $publication = $pubRepo->find($publicationId);
        if (!$publication) {
            return $this->json(['success' => false, 'message' => 'Publication not found.'], 404);
        }

        $user = null;
        if (!empty($userId)) {
            $user = $userRepo->find($userId);
        }

        $commentaire->setContent($content);
        $commentaire->setUser($user);
        $commentaire->setPublication($publication);
        $commentaire->setStatus($status);
        $commentaire->setNbLikes($nbLikes);
        $commentaire->setParentId($parentId);
        $commentaire->setEstSignale($estSignale);
        $commentaire->setRaisonSignalement($estSignale ? $raisonSignalement : null);

        $em->flush();

        return $this->json(['success' => true, 'message' => 'Commentaire updated successfully!', 'commentaire' => $this->buildCommentaireData($commentaire)]);
    }

    #[Route('/commentaire/{id}/delete', name: 'commentaire_delete', methods: ['POST'])]
    public function commentaireDelete(int $id, EntityManagerInterface $em, CommentaireRepository $repo): JsonResponse
    {
        $commentaire = $repo->find($id);
        if (!$commentaire) {
            return $this->json(['success' => false, 'message' => 'Commentaire not found.'], 404);
        }

        $em->remove($commentaire);
        $em->flush();

        return $this->json(['success' => true, 'message' => 'Commentaire deleted successfully!']);
    }

    #[Route('/commentaire/{id}/get', name: 'commentaire_get', methods: ['GET'])]
    public function commentaireGet(int $id, CommentaireRepository $repo): JsonResponse
    {
        $commentaire = $repo->find($id);
        if (!$commentaire) {
            return $this->json(['success' => false, 'message' => 'Commentaire not found.'], 404);
        }
        return $this->json(['success' => true, 'commentaire' => $this->buildCommentaireData($commentaire)]);
    }

    private function buildCommentaireData(Commentaire $com): array
    {
        return [
            'id' => $com->getIdCommentaire(),
            'content' => $com->getContent(),
            'status' => $com->getStatus(),
            'nbLikes' => $com->getNbLikes() ?? 0,
            'nbDislikes' => $com->getNbDislikes() ?? 0,
            'parentId' => $com->getParentId(),
            'estSignale' => $com->isEstSignale(),
            'raisonSignalement' => $com->getRaisonSignalement(),
            'dateCreation' => $com->getDateCreation()?->format('Y-m-d H:i'),
            'dateFmt' => $com->getDateCreation()?->format('M d, Y'),
            'userId' => $com->getUser()?->getId(),
            'userName' => $com->getUser() ? $com->getUser()->getPrenom() . ' ' . $com->getUser()->getNom() : 'Anonymous',
            'publicationId' => $com->getPublication()?->getIdPublication(),
            'publicationTitle' => $com->getPublication()?->getTitre(),
        ];
    }

    /**
     * Publications – Full publication management page (list, add, edit, delete).
     */
    #[Route('/publication', name: 'publications')]
    public function publications(PublicationRepository $repo): Response
    {
        $publications = $repo->findAllOrderedByDate();
        $total = count($publications);
        $thisMonth = count(array_filter($publications, fn(Publication $p) => $p->getDateAct() && $p->getDateAct()->format('Y-m') === date('Y-m')));
        $withImages = count(array_filter($publications, fn(Publication $p) => $p->getImage() && $p->getImage() !== 'default.jpg'));

        return $this->render('admin/publications.html.twig', [
            'publications' => $publications,
            'totalCount' => $total,
            'thisMonthCount' => $thisMonth,
            'withImagesCount' => $withImages,
        ]);
    }

    // ─── AJAX: Publication CRUD ─────────────────────────────

    #[Route('/publication/search', name: 'publication_search', methods: ['GET'])]
    public function publicationSearch(Request $request, PublicationRepository $repo): JsonResponse
    {
        $q = trim($request->query->get('q', ''));
        $sort = $request->query->get('sort', 'date');
        $order = strtoupper($request->query->get('order', 'DESC'));
        if (!in_array($order, ['ASC', 'DESC'])) $order = 'DESC';

        $qb = $repo->createQueryBuilder('p');
        if ($q !== '') {
            $qb->andWhere('p.titre LIKE :q OR p.description LIKE :q')
               ->setParameter('q', '%' . $q . '%');
        }
        $sortField = match ($sort) {
            'titre' => 'p.titre',
            'id' => 'p.idPublication',
            default => 'p.dateAct',
        };
        $qb->orderBy($sortField, $order);
        $publications = $qb->getQuery()->getResult();

        $data = [];
        foreach ($publications as $pub) {
            $data[] = $this->buildPublicationData($pub);
        }

        return $this->json(['results' => $data, 'count' => count($data)]);
    }

    #[Route('/publication/create', name: 'publication_create', methods: ['POST'])]
    public function publicationCreate(Request $request, EntityManagerInterface $em, PublicationRepository $repo): JsonResponse
    {
        $titre = strip_tags(trim($request->request->get('titre', '')));
        $description = strip_tags(trim($request->request->get('description', '')));
        $dateStr = $request->request->get('date', '');

        // Validation
        if (empty($titre) || strlen($titre) < 3) {
            return $this->json(['success' => false, 'message' => 'Title must be at least 3 characters.'], 400);
        }
        if (empty($description) || strlen($description) < 10) {
            return $this->json(['success' => false, 'message' => 'Description must be at least 10 characters.'], 400);
        }

        $publication = new Publication();
        $publication->setTitre($titre);
        $publication->setDescription($description);
        $publication->setUser($this->getUser());
        $publication->setDateAct(!empty($dateStr) ? new \DateTime($dateStr) : new \DateTime());

        // Ensure unique slug
        $slugger = new AsciiSlugger();
        $baseSlug = strtolower($slugger->slug($titre));
        $slug = $baseSlug;
        $i = 1;
        while ($repo->findOneBy(['slug' => $slug])) {
            $slug = $baseSlug . '-' . $i++;
        }
        $publication->setSlug($slug);

        // Handle image upload
        $imageFile = $request->files->get('image');
        if ($imageFile) {
            $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!in_array($imageFile->getMimeType(), $allowedMimeTypes)) {
                return $this->json(['success' => false, 'message' => 'Invalid image type. JPG, PNG, GIF, WEBP only.'], 400);
            }
            if ($imageFile->getSize() > 5242880) {
                return $this->json(['success' => false, 'message' => 'Image too large (max 5MB).'], 400);
            }
            $newFilename = uniqid('pub_') . '.' . $imageFile->guessExtension();
            try {
                $imageFile->move($this->getParameter('kernel.project_dir') . '/public/uploads', $newFilename);
                $publication->setImage($newFilename);
            } catch (\Exception $e) {
                return $this->json(['success' => false, 'message' => 'Image upload failed.'], 500);
            }
        } else {
            $publication->setImage('default.jpg');
        }

        $em->persist($publication);
        $em->flush();

        return $this->json(['success' => true, 'message' => 'Publication created successfully!', 'publication' => $this->buildPublicationData($publication)]);
    }

    #[Route('/publication/{id}/update', name: 'publication_update', methods: ['POST'])]
    public function publicationUpdate(int $id, Request $request, EntityManagerInterface $em, PublicationRepository $repo): JsonResponse
    {
        $publication = $repo->find($id);
        if (!$publication) {
            return $this->json(['success' => false, 'message' => 'Publication not found.'], 404);
        }

        $titre = strip_tags(trim($request->request->get('titre', '')));
        $description = strip_tags(trim($request->request->get('description', '')));
        $dateStr = $request->request->get('date', '');

        if (empty($titre) || strlen($titre) < 3) {
            return $this->json(['success' => false, 'message' => 'Title must be at least 3 characters.'], 400);
        }
        if (empty($description) || strlen($description) < 10) {
            return $this->json(['success' => false, 'message' => 'Description must be at least 10 characters.'], 400);
        }

        $publication->setTitre($titre);
        $publication->setDescription($description);
        if (!empty($dateStr)) {
            $publication->setDateAct(new \DateTime($dateStr));
        }

        // Update slug
        $slugger = new AsciiSlugger();
        $baseSlug = strtolower($slugger->slug($titre));
        $slug = $baseSlug;
        $i = 1;
        while ($existing = $repo->findOneBy(['slug' => $slug])) {
            if ($existing->getIdPublication() === $publication->getIdPublication()) break;
            $slug = $baseSlug . '-' . $i++;
        }
        $publication->setSlug($slug);

        // Handle image upload
        $imageFile = $request->files->get('image');
        if ($imageFile) {
            $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!in_array($imageFile->getMimeType(), $allowedMimeTypes)) {
                return $this->json(['success' => false, 'message' => 'Invalid image type.'], 400);
            }
            if ($imageFile->getSize() > 5242880) {
                return $this->json(['success' => false, 'message' => 'Image too large (max 5MB).'], 400);
            }
            $newFilename = uniqid('pub_') . '.' . $imageFile->guessExtension();
            try {
                $imageFile->move($this->getParameter('kernel.project_dir') . '/public/uploads', $newFilename);
                // Delete old image
                $oldImage = $publication->getImage();
                if ($oldImage && $oldImage !== 'default.jpg') {
                    $oldPath = $this->getParameter('kernel.project_dir') . '/public/uploads/' . $oldImage;
                    if (file_exists($oldPath)) unlink($oldPath);
                }
                $publication->setImage($newFilename);
            } catch (\Exception $e) {
                return $this->json(['success' => false, 'message' => 'Image upload failed.'], 500);
            }
        }

        $em->flush();

        return $this->json(['success' => true, 'message' => 'Publication updated successfully!', 'publication' => $this->buildPublicationData($publication)]);
    }

    #[Route('/publication/{id}/delete', name: 'publication_delete', methods: ['POST'])]
    public function publicationDelete(int $id, EntityManagerInterface $em, PublicationRepository $repo): JsonResponse
    {
        $publication = $repo->find($id);
        if (!$publication) {
            return $this->json(['success' => false, 'message' => 'Publication not found.'], 404);
        }

        // Delete image file
        $image = $publication->getImage();
        if ($image && $image !== 'default.jpg') {
            $path = $this->getParameter('kernel.project_dir') . '/public/uploads/' . $image;
            if (file_exists($path)) unlink($path);
        }

        $em->remove($publication);
        $em->flush();

        return $this->json(['success' => true, 'message' => 'Publication deleted successfully!']);
    }

    #[Route('/publication/{id}/get', name: 'publication_get', methods: ['GET'])]
    public function publicationGet(int $id, PublicationRepository $repo): JsonResponse
    {
        $publication = $repo->find($id);
        if (!$publication) {
            return $this->json(['success' => false, 'message' => 'Publication not found.'], 404);
        }
        return $this->json(['success' => true, 'publication' => $this->buildPublicationData($publication)]);
    }

    private function buildPublicationData(Publication $pub): array
    {
        return [
            'id' => $pub->getIdPublication(),
            'titre' => $pub->getTitre(),
            'description' => $pub->getDescription(),
            'image' => $pub->getImage(),
            'imageUrl' => $pub->getImage() && $pub->getImage() !== 'default.jpg' ? '/uploads/' . $pub->getImage() : null,
            'date' => $pub->getDateAct()?->format('Y-m-d'),
            'dateFmt' => $pub->getDateAct()?->format('M d, Y'),
            'slug' => $pub->getSlug(),
            'nbLikes' => $pub->getNbLikes() ?? 0,
            'nbDislikes' => $pub->getNbDislikes() ?? 0,
            'user' => $pub->getUser() ? $pub->getUser()->getPrenom() . ' ' . $pub->getUser()->getNom() : null,
        ];
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

    // ─── QR CODE SCANNER ───────────────────────────────────

    /**
     * Scanner page — staff opens this on their phone to scan QR codes.
     */
    #[Route('/scan', name: 'scan')]
    public function scan(): Response
    {
        return $this->render('admin/scan.html.twig');
    }

    /**
     * AJAX endpoint: verify a scanned QR code and confirm participation.
     */
    #[Route('/scan/verify', name: 'scan_verify', methods: ['POST'])]
    public function scanVerify(Request $request, ParticipationRepository $repo, ManagerRegistry $m): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $qrText = $data['qr'] ?? '';

        // Parse participation ID from QR text (first line: "SCAN:123")
        $participationId = null;
        if (preg_match('/^SCAN:(\d+)/m', $qrText, $matches)) {
            $participationId = (int) $matches[1];
        } elseif (preg_match('/Participation #(\d+)/m', $qrText, $matches)) {
            $participationId = (int) $matches[1];
        }

        if (!$participationId) {
            return $this->json([
                'success' => false,
                'message' => 'QR code invalide — impossible de lire l\'identifiant de participation.',
            ], 400);
        }

        $participation = $repo->find($participationId);

        if (!$participation) {
            return $this->json([
                'success' => false,
                'message' => 'Participation #' . $participationId . ' introuvable.',
            ], 404);
        }

        $evt = $participation->getEvenement();

        // Check if already scanned
        if ($participation->isScanned()) {
            return $this->json([
                'success' => false,
                'already_scanned' => true,
                'message' => 'Ce QR code a déjà été scanné le ' . ($participation->getScannedAt()?->format('d/m/Y à H:i') ?? ''),
                'participation' => $this->buildParticipationData($participation),
            ], 409);
        }

        // Cancelled
        if ($participation->getStatut() === 'Annulée') {
            return $this->json([
                'success' => false,
                'message' => 'Cette participation a été annulée.',
            ], 400);
        }

        $em = $m->getManager();
        $confirmedNow = false;

        // Any "En attente" (Cash, Carte, Gratuit) → Confirm on scan
        if ($participation->getStatut() === 'En attente') {
            $participation->setStatut('Confirmée');
            $confirmedNow = true;
        }

        $participation->setScanned(true);
        $participation->setScannedAt(new \DateTime());
        $em->flush();

        $mode = $participation->getModePaiement() ?? 'Gratuit';
        $msg = $confirmedNow
            ? 'Participation confirmée ! (' . $mode . ') Statut changé à "Confirmée".'
            : 'Participation déjà confirmée. QR marqué comme utilisé.';

        return $this->json([
            'success' => true,
            'message' => $msg,
            'confirmed_now' => $confirmedNow,
            'participation' => $this->buildParticipationData($participation),
        ]);
    }

    /**
     * AJAX: return all previously scanned participations (for table persistence).
     */
    #[Route('/scan/list', name: 'scan_list', methods: ['GET'])]
    public function scanList(ParticipationRepository $repo): JsonResponse
    {
        $scanned = $repo->findBy(['scanned' => true], ['scannedAt' => 'DESC']);
        $data = array_map(fn(Participation $p) => $this->buildParticipationData($p), $scanned);
        return $this->json(['results' => $data]);
    }

    #[Route('/evenement/generate-description', name: 'generate_description', methods: ['POST'])]
    public function generateDescription(Request $request, HttpClientInterface $http): JsonResponse
    {
        $nom  = $request->request->get('nom', '');
        $type = $request->request->get('type', '');

        $prompt = "Génère une description courte et attractive (3 phrases max) en français pour un événement culturel nommé '{$nom}' de type '{$type}'. Sois enthousiaste et professionnel. Ne mets pas de guillemets autour de la réponse.";

        try {
            $response = $http->request('POST',
                'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=' . $_ENV['GEMINI_API_KEY'],
                [
                    'json' => [
                        'contents' => [['parts' => [['text' => $prompt]]]]
                    ]
                ]
            );

            $data = $response->toArray();
            $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;

            if ($text) {
                return $this->json(['success' => true, 'description' => trim($text)]);
            }
        } catch (\Throwable $e) {
            // Fall through to local generation
        }

        // ── Fallback: smart local description generator ──
        $text = $this->generateLocalDescription($nom, $type);
        return $this->json(['success' => true, 'description' => $text]);
    }

    private function generateLocalDescription(string $nom, string $type): string
    {
        $templates = [
            'Concert' => [
                "Vibrez au rythme de **{nom}**, une soirée musicale exceptionnelle qui promet de vous faire voyager à travers des mélodies envoûtantes. Des artistes talentueux monteront sur scène pour offrir une performance inoubliable. Ne manquez pas cet événement unique qui marquera les esprits !",
                "Plongez dans l'univers sonore de **{nom}**, un concert qui réunit passion et virtuosité dans une atmosphère électrisante. Une expérience musicale rare qui ravira les amateurs de belles performances. Réservez vite vos places pour cette soirée d'exception !",
            ],
            'Exposition' => [
                "Découvrez **{nom}**, une exposition qui célèbre la créativité et l'expression artistique sous toutes ses formes. Des œuvres captivantes vous invitent à explorer de nouveaux horizons visuels et émotionnels. Une rencontre avec l'art contemporain à ne pas manquer !",
                "**{nom}** vous ouvre les portes d'un univers artistique fascinant, où chaque œuvre raconte une histoire unique. Cette exposition met en lumière des talents exceptionnels et des créations qui repoussent les limites de l'imagination. Venez vous laisser surprendre et émerveiller !",
            ],
            'Conférence' => [
                "Rejoignez **{nom}**, une conférence enrichissante qui rassemble experts et passionnés autour de thématiques actuelles et inspirantes. Des intervenants de renom partageront leurs connaissances et visions pour nourrir votre réflexion. Un rendez-vous intellectuel incontournable pour élargir vos horizons !",
                "**{nom}** est une conférence dédiée à l'échange de savoirs et à l'innovation, offrant une plateforme unique de partage et de réflexion. Des experts chevronnés animeront des discussions stimulantes sur des sujets d'actualité. Venez enrichir vos connaissances et élargir votre réseau professionnel !",
            ],
            'Atelier' => [
                "Participez à **{nom}**, un atelier interactif et convivial conçu pour développer vos compétences et stimuler votre créativité. Encadrés par des professionnels passionnés, vous repartirez avec de nouvelles techniques et une expérience enrichissante. Inscrivez-vous dès maintenant pour cette session pratique et inspirante !",
                "**{nom}** est un atelier immersif qui vous permet d'apprendre en pratiquant dans une ambiance bienveillante et dynamique. Que vous soyez débutant ou confirmé, vous découvrirez de nouvelles approches et affûterez vos talents. Une opportunité unique de progresser entouré de passionnés comme vous !",
            ],
            'Festival' => [
                "Vivez l'effervescence de **{nom}**, un festival vibrant qui célèbre la diversité culturelle et artistique dans une ambiance festive et chaleureuse. Une programmation riche et variée vous attend pour des moments de partage et de découverte inoubliables. Rejoignez la fête et laissez-vous emporter par l'énergie communicative de cet événement unique !",
                "**{nom}** est le festival incontournable de la saison, rassemblant artistes, créateurs et public autour d'une célébration de la culture et de la créativité. Des jours entiers de spectacles, d'expositions et de rencontres vous promettent une expérience totale et mémorable. Ne manquez pas ce rendez-vous festif qui fait battre le cœur de notre communauté !",
            ],
        ];

        $default = [
            "Découvrez **{nom}**, un événement {type} exceptionnel qui promet une expérience inoubliable pour tous les participants. Une occasion unique de se réunir, partager et créer des souvenirs mémorables dans une atmosphère conviviale et chaleureuse. Réservez votre place dès maintenant et faites partie de cette aventure extraordinaire !",
            "**{nom}** est un événement {type} incontournable qui réunit passionnés et curieux autour d'une expérience enrichissante et inspirante. Des moments forts et des rencontres mémorables vous attendent dans un cadre soigneusement préparé pour votre plus grand plaisir. Ne ratez pas cette opportunité exceptionnelle — les places sont limitées !",
        ];

        $pool = $templates[$type] ?? $default;
        $tpl  = $pool[array_rand($pool)];

        return str_replace(
            ['{nom}', '**{nom}**', '{type}'],
            [$nom, $nom, strtolower($type)],
            $tpl
        );
    }

    private function buildParticipationData(Participation $p): array
    {
        $evt = $p->getEvenement();
        return [
            'id' => $p->getId(),
            'statut' => $p->getStatut(),
            'modePaiement' => $p->getModePaiement() ?? 'Gratuit',
            'nbrParticipation' => $p->getNbrParticipation(),
            'dateParticipation' => $p->getDateParticipation()?->format('d/m/Y'),
            'scannedAt' => $p->getScannedAt()?->format('d/m/Y H:i'),
            'evenement' => $evt ? [
                'nom' => $evt->getNom(),
                'date' => $evt->getDate()?->format('d/m/Y'),
                'heure' => $evt->getHeure()?->format('H:i'),
                'lieu' => $evt->getLieu(),
                'type' => $evt->getTypeEvenement(),
            ] : null,
        ];
    }
      


    
}
