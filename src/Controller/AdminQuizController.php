<?php

namespace App\Controller;

use App\Entity\Quiz;
use App\Form\QuizType;
use App\Repository\QuizRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/quiz')]
final class AdminQuizController extends AbstractController
{
    #[Route(name: 'app_admin_quiz_index', methods: ['GET'])]
    public function index(QuizRepository $quizRepository): Response
    {
        return $this->render('admin_quiz/index.html.twig', [
            'quizzes' => $quizRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_admin_quiz_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $quiz = new Quiz();
        $form = $this->createForm(QuizType::class, $quiz);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($quiz);
            $entityManager->flush();

            return $this->redirectToRoute('app_admin_quiz_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin_quiz/new.html.twig', [
            'quiz' => $quiz,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_quiz_show', methods: ['GET'])]
    public function show(Quiz $quiz): Response
    {
        return $this->render('admin_quiz/show.html.twig', [
            'quiz' => $quiz,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_quiz_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Quiz $quiz, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(QuizType::class, $quiz);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_admin_quiz_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin_quiz/edit.html.twig', [
            'quiz' => $quiz,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_quiz_delete', methods: ['POST'])]
    public function delete(Request $request, Quiz $quiz, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$quiz->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($quiz);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_admin_quiz_index', [], Response::HTTP_SEE_OTHER);
    }
}
