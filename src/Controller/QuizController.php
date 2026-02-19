<?php

namespace App\Controller;

use App\Entity\Quiz;
use App\Entity\Resultat;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class QuizController extends AbstractController
{
    #[Route('/quiz/{id}', name: 'quiz_pass')]
    public function pass(Quiz $quiz, Request $request, EntityManagerInterface $em): Response
    {
        $questions = $quiz->getQuestions();
        
        if ($request->isMethod('POST')) {
            $score = 0;
            $total = count($questions);

          foreach ($questions as $question) {
    $userAnswer = $request->request->get('question_' . $question->getId());
    
    $correct = strtoupper(trim($question->getCorrectAnswer()));
    $submitted = strtoupper(trim($userAnswer));

    // AJOUTEZ CES DUMPS POUR VOIR LES VALEURS DANS VOTRE NAVIGATEUR
    // dump("Soumis: " . $submitted);
    // dump("Attendu: " . $correct);

    if ($submitted === $correct && $submitted !== "") {
        $score++;
    }
}



            // Enregistrement du résultat
            $result = new Resultat();
            $result->setQuiz($quiz);
            $result->setScore($score);
            // Réussite si score >= 50%
            $result->setIsPassed($score >= ($total / 2));

            $em->persist($result);
            $em->flush();

            return $this->render('quiz/result.html.twig', [
                'score' => $score,
                'total' => $total,
                'isPassed' => $result->isPassed(),
                'quiz' => $quiz
            ]);
        }

        return $this->render('quiz/pass.html.twig', [
            'quiz' => $quiz
        ]);
    }
}