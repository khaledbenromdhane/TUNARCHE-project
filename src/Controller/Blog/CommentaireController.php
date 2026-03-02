<?php

namespace App\Controller\Blog;

use App\Entity\Blog\Commentaire;
use App\Entity\Blog\Publication;
use App\Repository\Blog\CommentaireRepository;
use App\Repository\Blog\PublicationRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\CommentModerationService;

#[Route('/blog/commentaire', name: 'blog_commentaire_')]
class CommentaireController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(CommentaireRepository $commentaireRepository, UserRepository $userRepository): Response
    {
        $commentaires = $commentaireRepository->findAll();
        $users = [];
        foreach ($userRepository->findAll() as $user) {
            $users[$user->getId()] = $user;
        }

        return $this->render('blog/commentaire/index.html.twig', [
            'commentaires' => $commentaires,
            'users' => $users,
        ]);
    }

    #[Route('/new/{publicationId}', name: 'new', methods: ['GET', 'POST'])]
    public function new(
        int $publicationId,
        Request $request,
        PublicationRepository $publicationRepository,
        EntityManagerInterface $entityManager,
        CommentModerationService $moderationService
    ): Response {
        $publication = $publicationRepository->find($publicationId);

        if (!$publication) {
            $this->addFlash('error', 'Publication not found!');
            return $this->redirectToRoute('blog_publication_index');
        }

        $connectedUser = $this->getUser();
        $hasError = false;

        if ($request->isMethod('POST')) {
            // Input validation - strip HTML tags to prevent XSS
            $content = strip_tags(trim($request->request->get('content', '')));
            $status = strip_tags($request->request->get('status', 'approved'));
            $parentIdInput = $request->request->get('parent_id', '0');

            // Validate content
            if (empty($content)) {
                $this->addFlash('error', 'Content is required.');
                $hasError = true;
            } elseif (strlen($content) < 2) {
                $this->addFlash('error', 'Content must be at least 2 characters long.');
                $hasError = true;
            } elseif (strlen($content) > 2000) {
                $this->addFlash('error', 'Content cannot exceed 2000 characters.');
                $hasError = true;
            }

            // Validate status
            $validStatuses = ['approved', 'pending', 'rejected'];
            if (!$hasError && !in_array($status, $validStatuses)) {
                $this->addFlash('error', 'Invalid status value.');
                $hasError = true;
            }

            // Validate parent_id
            $parentId = 0;
            if (!$hasError) {
                if ($parentIdInput !== '' && !is_numeric($parentIdInput)) {
                    $this->addFlash('error', 'Invalid parent comment ID.');
                    $hasError = true;
                } else {
                    $parentId = (is_numeric($parentIdInput) && $parentIdInput !== '') ? (int)$parentIdInput : 0;
                    if ($parentId < 0) {
                        $this->addFlash('error', 'Parent comment ID cannot be negative.');
                        $hasError = true;
                    }
                }
            }

            if (!$hasError) {
                $commentaire = new Commentaire();
                $commentaire->setContent($content);
                
                // Moderation check
                $modResult = $moderationService->moderate($content);
                if (!$modResult['is_clean']) {
                    $status = $modResult['suggested_status'];
                    $commentaire->setEstSignale(true);
                    $commentaire->setRaisonSignalement($modResult['reason']);
                    $this->addFlash('warning', 'Your comment has been submitted but is pending review due to our community guidelines.');
                } else {
                    $this->addFlash('success', 'Comment added successfully!');
                }

                $commentaire->setStatus($status);
                $commentaire->setPublication($publication);
                $commentaire->setUser($connectedUser);
                $commentaire->setParentId($parentId);

                $entityManager->persist($commentaire);
                $entityManager->flush();

                return $this->redirectToRoute('blog_publication_show', ['slug' => $publication->getSlug()]);
            }
            
            // If there's an error, fall through to render the form again
        }

        return $this->render('blog/commentaire/new.html.twig', [
            'publication' => $publication,
            'connectedUser' => $connectedUser,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Commentaire $commentaire,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        CommentModerationService $moderationService
    ): Response {
        $hasError = false;

        if ($request->isMethod('POST')) {
            // Input validation - strip HTML tags to prevent XSS
            $content = strip_tags(trim($request->request->get('content', '')));
            $idUser = $request->request->get('id_user');
            $status = strip_tags($request->request->get('status', 'approved'));
            $parentIdInput = $request->request->get('parent_id', '0');

            // Validate content
            if (empty($content)) {
                $this->addFlash('error', 'Content is required.');
                $hasError = true;
            } elseif (strlen($content) < 2) {
                $this->addFlash('error', 'Content must be at least 2 characters long.');
                $hasError = true;
            } elseif (strlen($content) > 2000) {
                $this->addFlash('error', 'Content cannot exceed 2000 characters.');
                $hasError = true;
            }

            // Validate user ID
            if (!$hasError && (empty($idUser) || !is_numeric($idUser) || (int)$idUser <= 0)) {
                $this->addFlash('error', 'Please select a valid user.');
                $hasError = true;
            }

            // Verify user exists
            if (!$hasError) {
                $user = $userRepository->find((int)$idUser);
                if (!$user) {
                    $this->addFlash('error', 'Selected user does not exist.');
                    $hasError = true;
                }
            }

            // Validate status
            $validStatuses = ['approved', 'pending', 'rejected'];
            if (!$hasError && !in_array($status, $validStatuses)) {
                $this->addFlash('error', 'Invalid status value.');
                $hasError = true;
            }

            // Validate parent_id
            $parentId = 0;
            if (!$hasError) {
                if ($parentIdInput !== '' && !is_numeric($parentIdInput)) {
                    $this->addFlash('error', 'Invalid parent comment ID.');
                    $hasError = true;
                } else {
                    $parentId = (is_numeric($parentIdInput) && $parentIdInput !== '') ? (int)$parentIdInput : 0;
                    if ($parentId < 0) {
                        $this->addFlash('error', 'Parent comment ID cannot be negative.');
                        $hasError = true;
                    }
                }
            }

            if (!$hasError) {
                $commentaire->setContent($content);
                
                // Moderation check
                $modResult = $moderationService->moderate($content);
                if (!$modResult['is_clean']) {
                    $status = $modResult['suggested_status'];
                    $commentaire->setEstSignale(true);
                    $commentaire->setRaisonSignalement($modResult['reason']);
                    $this->addFlash('warning', 'Your edited comment is pending review due to our community guidelines.');
                } else {
                    $this->addFlash('success', 'Comment updated successfully!');
                }

                $commentaire->setUser($user);
                $commentaire->setStatus($status);
                $commentaire->setParentId($parentId);

                $entityManager->flush();

                // Redirect back to admin if editing from admin panel
                $fromAdmin = $request->query->get('from') === 'admin' || $request->request->get('from') === 'admin';
                if ($fromAdmin) {
                    return $this->redirectToRoute('app_admin_commentaires');
                }

                return $this->redirectToRoute('blog_publication_show', [
                    'slug' => $commentaire->getPublication()->getSlug()
                ]);
            }
            
            // If there's an error, fall through to render the form again
        }

        $users = $userRepository->findAllOrderedByName();

        return $this->render('blog/commentaire/edit.html.twig', [
            'commentaire' => $commentaire,
            'users' => $users,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(
        Request $request,
        Commentaire $commentaire,
        EntityManagerInterface $entityManager
    ): Response {
        $publicationId = $commentaire->getPublication()->getIdPublication();

        if ($this->isCsrfTokenValid('delete'.$commentaire->getIdCommentaire(), $request->request->get('_token'))) {
            $entityManager->remove($commentaire);
            $entityManager->flush();

            $this->addFlash('success', 'Comment deleted successfully!');
        }

        return $this->redirectToRoute('blog_publication_show', ['slug' => $commentaire->getPublication()->getSlug()]);
    }

    #[Route('/{id}/react', name: 'react', methods: ['POST'])]
    public function react(
        Request $request,
        Commentaire $commentaire,
        EntityManagerInterface $entityManager,
        \App\Repository\Blog\CommentaireReactionRepository $reactionRepository
    ): JsonResponse {
        // Allow providing a mock id_user for testing without full auth
        $userId = $request->get('id_user', 1);
        $isLike = filter_var($request->get('is_like'), FILTER_VALIDATE_BOOLEAN);

        $reaction = $reactionRepository->findOneBy(['commentaire' => $commentaire, 'user' => $userId]);

        // Ensure counts are not null
        $commentaire->setNbLikes($commentaire->getNbLikes() ?? 0);
        $commentaire->setNbDislikes($commentaire->getNbDislikes() ?? 0);

        if ($reaction) {
            if ($reaction->isLike() === $isLike) {
                // Remove reaction if clicking the same button
                $entityManager->remove($reaction);
                if ($isLike) {
                    $commentaire->setNbLikes(max(0, $commentaire->getNbLikes() - 1));
                } else {
                    $commentaire->setNbDislikes(max(0, $commentaire->getNbDislikes() - 1));
                }
            } else {
                // Swap reaction
                $reaction->setIsLike($isLike);
                if ($isLike) {
                    $commentaire->setNbLikes($commentaire->getNbLikes() + 1);
                    $commentaire->setNbDislikes(max(0, $commentaire->getNbDislikes() - 1));
                } else {
                    $commentaire->setNbDislikes($commentaire->getNbDislikes() + 1);
                    $commentaire->setNbLikes(max(0, $commentaire->getNbLikes() - 1));
                }
            }
        } else {
            // New reaction
            $reaction = new \App\Entity\Blog\CommentaireReaction();
            $reaction->setCommentaire($commentaire);
            $reaction->setUser($entityManager->getReference(\App\Entity\User::class, (int)$userId));
            $reaction->setIsLike($isLike);
            $entityManager->persist($reaction);

            if ($isLike) {
                $commentaire->setNbLikes($commentaire->getNbLikes() + 1);
            } else {
                $commentaire->setNbDislikes($commentaire->getNbDislikes() + 1);
            }
        }

        $entityManager->flush();

        return $this->json([
            'success' => true,
            'nbLikes' => $commentaire->getNbLikes(),
            'nbDislikes' => $commentaire->getNbDislikes()
        ]);
    }

    #[Route('/{id}/flag', name: 'flag', methods: ['POST'])]
    public function flag(
        Request $request,
        Commentaire $commentaire,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $commentaire->setEstSignale(true);
        $commentaire->setRaisonSignalement($request->request->get('raison', 'No reason provided'));

        $entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Comment flagged successfully',
        ]);
    }

    #[Route('/{id}/unflag', name: 'unflag', methods: ['POST'])]
    public function unflag(Commentaire $commentaire, EntityManagerInterface $entityManager): JsonResponse
    {
        $commentaire->setEstSignale(false);
        $commentaire->setRaisonSignalement(null);

        $entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Comment unflagged successfully',
        ]);
    }

    #[Route('/flagged', name: 'flagged', methods: ['GET'])]
    public function flagged(CommentaireRepository $commentaireRepository): Response
    {
        $flaggedComments = $commentaireRepository->findFlagged();

        return $this->render('blog/commentaire/flagged.html.twig', [
            'commentaires' => $flaggedComments,
        ]);
    }

    #[Route('/replies/{parentId}', name: 'replies', methods: ['GET'])]
    public function replies(int $parentId, CommentaireRepository $commentaireRepository): JsonResponse
    {
        $replies = $commentaireRepository->findRepliesByParentId($parentId);

        return $this->json([
            'success' => true,
            'replies' => array_map(function($reply) {
                return [
                    'id' => $reply->getIdCommentaire(),
                    'status' => $reply->getStatus(),
                    'likes' => $reply->getNbLikes(),
                    'userId' => $reply->getUser()?->getId(),
                ];
            }, $replies),
        ]);
    }
}
