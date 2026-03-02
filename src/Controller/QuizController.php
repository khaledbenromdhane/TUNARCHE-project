<?php

namespace App\Controller;

use App\Entity\Quiz;
use App\Entity\Resultat;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Dompdf\Dompdf;
use Dompdf\Options;

class QuizController extends AbstractController
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    #[Route('/quiz/{id}', name: 'quiz_pass')]
    public function pass(Quiz $quiz, Request $request): Response
    {
        $questions = $quiz->getQuestions();

        if ($request->isMethod('POST')) {
            $score = 0;
            $total = count($questions);

            foreach ($questions as $question) {
                $userAnswer  = $request->request->get('question_' . $question->getId());
                $correct     = strtoupper(trim($question->getCorrectAnswer()));
                $submitted   = strtoupper(trim((string) $userAnswer));

                if ($submitted !== '' && $submitted === $correct) {
                    $score++;
                }
            }

            // Calcul badge
            $percentage = $total > 0 ? ($score / $total) * 100 : 0;
            $badge = match(true) {
                $percentage >= 80 => 'Gold',
                $percentage >= 60 => 'Silver',
                default           => 'Bronze',
            };

            // Enregistrement résultat
            $result = new Resultat();
            $result->setQuiz($quiz);
            $result->setScore($score);
            $result->setIsPassed($score >= ($total / 2));

            $this->em->persist($result);
            $this->em->flush();

            return $this->render('quiz/result.html.twig', [
                'score'    => $score,
                'total'    => $total,
                'isPassed' => $result->isPassed(),
                'badge'    => $badge,
                'quiz'     => $quiz,
            ]);
        }

        return $this->render('quiz/pass.html.twig', [
            'quiz' => $quiz,
        ]);
    }

    #[Route('/quiz/{id}/certificat', name: 'quiz_certificat')]
    public function certificat(Quiz $quiz): Response
    {
        // Dernier résultat pour ce quiz
        $resultat = $this->em->getRepository(Resultat::class)
            ->findOneBy(['quiz' => $quiz], ['id' => 'DESC']);

        // Vérification réussite
        if (!$resultat || !$resultat->isPassed()) {
            $this->addFlash('danger', 'Vous devez réussir le quiz pour obtenir un certificat.');
            return $this->redirectToRoute('quiz_pass', ['id' => $quiz->getId()]);
        }

        $score      = $resultat->getScore();
        $total      = $quiz->getQuestions()->count();
        $percentage = $total > 0 ? ($score / $total) * 100 : 0;

        // Badge
        $badge = match(true) {
            $percentage >= 80 => '🥇 Gold',
            $percentage >= 60 => '🥈 Silver',
            default           => '🥉 Bronze',
        };

        // Rendu HTML du certificat
        $html = $this->renderView('pdf/certificat.html.twig', [
            'formation'       => $quiz->getFormation(),
            'participantName' => 'Participant',
            'score'           => $score,
            'total'           => $total,
            'badge'           => $badge,
            'percentage'      => round($percentage),
        ]);

        // Génération PDF
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'serif');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        $filename = 'certificat-' . uniqid() . '.pdf';

        return new Response(
            $dompdf->output(),
            200,
            [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $filename . '"',
            ]
        );
    }
}