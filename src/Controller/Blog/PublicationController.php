<?php

namespace App\Controller\Blog;

use App\Entity\Blog\Publication;
use App\Repository\Blog\PublicationRepository;
use App\Repository\Blog\CommentaireRepository;
use App\Repository\UserRepository;
use App\Service\ImageAnalysisService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/blog/publication', name: 'blog_publication_')]
class PublicationController extends AbstractController
{
    public function __construct(
        private readonly ImageAnalysisService $imageAnalysisService
    ) {}
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(PublicationRepository $publicationRepository, UserRepository $userRepository): Response
    {
        $publications = $publicationRepository->findAllOrderedByDate();
        $users = [];
        foreach ($userRepository->findAll() as $user) {
            $users[$user->getId()] = $user;
        }

        return $this->render('blog/publication/index.html.twig', [
            'publications' => $publications,
            'users' => $users,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, UserRepository $userRepository, PublicationRepository $publicationRepository): Response
    {
        $connectedUser = $this->getUser();
        $hasError = false;

        if ($request->isMethod('POST')) {
            // Input validation - strip HTML tags to prevent XSS
            $titre = strip_tags(trim($request->request->get('titre', '')));
            $description = strip_tags(trim($request->request->get('description', '')));

            // Validate titre
            if (empty($titre)) {
                $this->addFlash('error', 'Title is required.');
                $hasError = true;
            } elseif (strlen($titre) < 3) {
                $this->addFlash('error', 'Title must be at least 3 characters long.');
                $hasError = true;
            } elseif (strlen($titre) > 255) {
                $this->addFlash('error', 'Title cannot exceed 255 characters.');
                $hasError = true;
            }

            // Validate description
            if (!$hasError && empty($description)) {
                $this->addFlash('error', 'Description is required.');
                $hasError = true;
            } elseif (!$hasError && strlen($description) < 10) {
                $this->addFlash('error', 'Description must be at least 10 characters long.');
                $hasError = true;
            } elseif (!$hasError && strlen($description) > 5000) {
                $this->addFlash('error', 'Description cannot exceed 5000 characters.');
                $hasError = true;
            }

            if (!$hasError) {
                $publication = new Publication();
                $publication->setTitre($titre);
                // Ensure unique slug
                $publication->setSlug($publicationRepository->findUniqueSlug($publication->getSlug()));
                $publication->setDescription($description);
                $publication->setUser($connectedUser);
                $publication->setDateAct(new \DateTime());
            }

            // Handle file upload
            $imageFile = $request->files->get('image');
            if ($imageFile && !$hasError) {
                // Validate file size (max 5MB)
                if ($imageFile->getSize() > 5242880) {
                    $this->addFlash('error', 'Image file is too large. Maximum size is 5MB.');
                    $hasError = true;
                }

                // Validate file type
                $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                if (!$hasError && !in_array($imageFile->getMimeType(), $allowedMimeTypes)) {
                    $this->addFlash('error', 'Invalid file type. Only JPG, PNG, GIF, and WEBP images are allowed.');
                    $hasError = true;
                }

                if (!$hasError && isset($publication)) {
                    $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = $this->slugify($originalFilename);
                    $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

                    try {
                        $imageFile->move(
                            $this->getParameter('kernel.project_dir') . '/public/uploads',
                            $newFilename
                        );
                        $publication->setImage($newFilename);

                        // --- Automatic image analysis + description generation ---
                        $fullPath = $this->getParameter('kernel.project_dir') . '/public/uploads/' . $newFilename;
                        $analysis = $this->imageAnalysisService->analyze($fullPath);
                        $publication->setImageAnalysis(json_encode($analysis));

                        // Auto-fill description if not provided
                        if (empty($description) && !empty($analysis['generated_description'])) {
                            $publication->setDescription($analysis['generated_description']);
                        }
                        // ---
                    } catch (\Exception $e) {
                        $this->addFlash('error', 'Failed to upload image: ' . $e->getMessage());
                        $hasError = true;
                    }
                }
            } elseif (!$hasError && isset($publication)) {
                $publication->setImage('default.jpg');
            }

            // Only persist and flush if there are no errors
            if (!$hasError && isset($publication)) {
                $entityManager->persist($publication);
                $entityManager->flush();

                $this->addFlash('success', 'Publication created successfully!');

                return $this->redirectToRoute('blog_publication_index');
            }
            
            // If there's an error, fall through to render the form again
            // No redirect needed - just continue to render below
        }

        return $this->render('blog/publication/new.html.twig', [
            'connectedUser' => $connectedUser,
        ]);
    }


    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Publication $publication,
        EntityManagerInterface $entityManager,
        PublicationRepository $publicationRepository
    ): Response {
        $hasError = false;

        if ($request->isMethod('POST')) {
            // Input validation - strip HTML tags to prevent XSS
            $titre = strip_tags(trim($request->request->get('titre', '')));
            $description = strip_tags(trim($request->request->get('description', '')));

            // Validate titre
            if (empty($titre)) {
                $this->addFlash('error', 'Title is required.');
                $hasError = true;
            } elseif (strlen($titre) < 3) {
                $this->addFlash('error', 'Title must be at least 3 characters long.');
                $hasError = true;
            } elseif (strlen($titre) > 255) {
                $this->addFlash('error', 'Title cannot exceed 255 characters.');
                $hasError = true;
            }

            // Validate description
            if (!$hasError && empty($description)) {
                $this->addFlash('error', 'Description is required.');
                $hasError = true;
            } elseif (!$hasError && strlen($description) < 10) {
                $this->addFlash('error', 'Description must be at least 10 characters long.');
                $hasError = true;
            } elseif (!$hasError && strlen($description) > 5000) {
                $this->addFlash('error', 'Description cannot exceed 5000 characters.');
                $hasError = true;
            }

            if (!$hasError) {
                $publication->setTitre($titre);
                // Ensure unique slug
                $publication->setSlug($publicationRepository->findUniqueSlug($publication->getSlug(), $publication->getIdPublication()));
                $publication->setDescription($description);
            }
            
            // Handle file upload
            $imageFile = $request->files->get('image');
            if ($imageFile) {
                // Validate file size (max 5MB)
                if ($imageFile->getSize() > 5242880) {
                    $this->addFlash('error', 'Image file is too large. Maximum size is 5MB.');
                    $hasError = true;
                }

                // Validate file type
                $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                if (!$hasError && !in_array($imageFile->getMimeType(), $allowedMimeTypes)) {
                    $this->addFlash('error', 'Invalid file type. Only JPG, PNG, GIF, and WEBP images are allowed.');
                    $hasError = true;
                }

                if (!$hasError) {
                    $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = $this->slugify($originalFilename);
                    $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

                    try {
                        $imageFile->move(
                            $this->getParameter('kernel.project_dir') . '/public/uploads',
                            $newFilename
                        );
                        
                        // Delete old image if it exists and is not default
                        $oldImage = $publication->getImage();
                        if ($oldImage && $oldImage !== 'default.jpg') {
                            $oldImagePath = $this->getParameter('kernel.project_dir') . '/public/uploads/' . $oldImage;
                            if (file_exists($oldImagePath)) {
                                unlink($oldImagePath);
                            }
                        }
                        
                        $publication->setImage($newFilename);

                        // --- Automatic image analysis ---
                        $fullPath = $this->getParameter('kernel.project_dir') . '/public/uploads/' . $newFilename;
                        $analysis = $this->imageAnalysisService->analyze($fullPath);
                        $publication->setImageAnalysis(json_encode($analysis));
                        // ---
                    } catch (\Exception $e) {
                        $this->addFlash('error', 'Failed to upload image: ' . $e->getMessage());
                        $hasError = true;
                    }
                }
            }

            // Only flush if there are no errors
            if (!$hasError) {
                $entityManager->flush();

                $this->addFlash('success', 'Publication updated successfully!');

                // Redirect back to admin if editing from admin panel
                $fromAdmin = $request->query->get('from') === 'admin' || $request->request->get('from') === 'admin';
                if ($fromAdmin) {
                    return $this->redirectToRoute('app_admin_publications');
                }

                return $this->redirectToRoute('blog_publication_show', ['slug' => $publication->getSlug()]);
            }
            
            // If there's an error, fall through to render the form again
        }

        return $this->render('blog/publication/edit.html.twig', [
            'publication' => $publication,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(
        Request $request,
        Publication $publication,
        EntityManagerInterface $entityManager
    ): Response {
        if ($this->isCsrfTokenValid('delete'.$publication->getIdPublication(), $request->request->get('_token'))) {
            $entityManager->remove($publication);
            $entityManager->flush();

            $this->addFlash('success', 'Publication deleted successfully!');
        }

        return $this->redirectToRoute('blog_publication_index');
    }

    #[Route('/search', name: 'search', methods: ['GET'])]
    #[Route('/{id}/react', name: 'react', methods: ['POST'])]
    public function react(
        Request $request,
        Publication $publication,
        EntityManagerInterface $entityManager,
        \App\Repository\Blog\PublicationReactionRepository $reactionRepository
    ): \Symfony\Component\HttpFoundation\JsonResponse {
        // Allow providing a mock id_user for testing without full auth
        $userId = $request->get('id_user', 1);
        $isLike = filter_var($request->get('is_like'), FILTER_VALIDATE_BOOLEAN);

        $reaction = $reactionRepository->findOneBy(['publication' => $publication, 'user' => $userId]);

        // Ensure counts are not null
        $publication->setNbLikes($publication->getNbLikes() ?? 0);
        $publication->setNbDislikes($publication->getNbDislikes() ?? 0);

        if ($reaction) {
            if ($reaction->isLike() === $isLike) {
                // Remove reaction if clicking the same button
                $entityManager->remove($reaction);
                if ($isLike) {
                    $publication->setNbLikes(max(0, $publication->getNbLikes() - 1));
                } else {
                    $publication->setNbDislikes(max(0, $publication->getNbDislikes() - 1));
                }
            } else {
                // Swap reaction
                $reaction->setIsLike($isLike);
                if ($isLike) {
                    $publication->setNbLikes($publication->getNbLikes() + 1);
                    $publication->setNbDislikes(max(0, $publication->getNbDislikes() - 1));
                } else {
                    $publication->setNbDislikes($publication->getNbDislikes() + 1);
                    $publication->setNbLikes(max(0, $publication->getNbLikes() - 1));
                }
            }
        } else {
            // New reaction
            $reaction = new \App\Entity\Blog\PublicationReaction();
            $reaction->setPublication($publication);
            $reaction->setUser($entityManager->getReference(\App\Entity\User::class, (int)$userId));
            $reaction->setIsLike($isLike);
            $entityManager->persist($reaction);

            if ($isLike) {
                $publication->setNbLikes($publication->getNbLikes() + 1);
            } else {
                $publication->setNbDislikes($publication->getNbDislikes() + 1);
            }
        }

        $entityManager->flush();

        return $this->json([
            'success' => true,
            'nbLikes' => $publication->getNbLikes(),
            'nbDislikes' => $publication->getNbDislikes()
        ]);
    }

    #[Route('/search', name: 'search', methods: ['GET'])]
    public function search(Request $request, PublicationRepository $publicationRepository): Response
    {
        $query = $request->query->get('q', '');
        $publications = $publicationRepository->searchPublications($query);

        return $this->render('blog/publication/index.html.twig', [
            'publications' => $publications,
            'search_query' => $query,
        ]);
    }

    #[Route('/{slug}', name: 'show', methods: ['GET'])]
    public function show(
        string $slug,
        PublicationRepository $publicationRepository,
        CommentaireRepository $commentaireRepository,
        UserRepository $userRepository
    ): Response {
        $publication = $publicationRepository->findOneBy(['slug' => $slug]);
        
        if (!$publication) {
            throw $this->createNotFoundException('The publication does not exist');
        }
        // Get all comments for this publication - using direct query
        $comments = $commentaireRepository->createQueryBuilder('c')
            ->where('c.publication = :publication')
            ->setParameter('publication', $publication)
            ->orderBy('c.idCommentaire', 'DESC')
            ->getQuery()
            ->getResult();
        
        $user = $publication->getUser();
        
        // Fetch all users for comment authors
        $users = [];
        foreach ($userRepository->findAll() as $u) {
            $users[$u->getId()] = $u;
        }

        // Fetch similar publications for recommendations
        $recommendations = $publicationRepository->findSimilar($publication, 3);

        return $this->render('blog/publication/show.html.twig', [
            'publication' => $publication,
            'comments' => $comments,
            'user' => $user,
            'users' => $users,
            'recommendations' => $recommendations,
        ]);
    }

    private function slugify(string $text): string
    {
        if (function_exists('transliterator_transliterate')) {
            return transliterator_transliterate('Any-Latin; Latin-ASCII; [^A-Za-z0-9_] remove; Lower()', $text);
        }

        // Fallback if intl is not active
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        $text = preg_replace('~[^-\w]+~', '', $text);
        $text = trim($text, '-');
        $text = preg_replace('~-+~', '-', $text);
        return strtolower($text);
    }
}
