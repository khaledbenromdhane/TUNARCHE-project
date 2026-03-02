<?php

namespace App\Controller;

use App\Entity\Question;
use App\Entity\Quiz;
use App\Form\QuestionType;
use App\Repository\QuestionRepository;
use App\Repository\QuizRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Route('/admin/question')]
final class QuestionController extends AbstractController
{
    private HttpClientInterface $httpClient;
    private string $geminiApiKey;

    public function __construct(HttpClientInterface $httpClient, string $geminiApiKey)
    {
        $this->httpClient   = $httpClient;
        $this->geminiApiKey = $geminiApiKey;
    }

    #[Route(name: 'app_question_index', methods: ['GET'])]
    public function index(QuestionRepository $questionRepository): Response
    {
        return $this->render('question/index.html.twig', [
            'questions' => $questionRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_question_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $question = new Question();
        $form = $this->createForm(QuestionType::class, $question);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($question);
            $entityManager->flush();
            $this->addFlash('success', 'Question ajoutée avec succès.');
            return $this->redirectToRoute('app_question_index');
        }

        return $this->render('question/new.html.twig', [
            'question' => $question,
            'form'     => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_question_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Question $question, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(QuestionType::class, $question);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Question mise à jour.');
            return $this->redirectToRoute('app_question_index');
        }

        return $this->render('question/edit.html.twig', [
            'question' => $question,
            'form'     => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_question_delete', methods: ['POST'])]
    public function delete(Request $request, Question $question, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $question->getId(), $request->request->get('_token'))) {
            $entityManager->remove($question);
            $entityManager->flush();
            $this->addFlash('warning', 'Question supprimée.');
        }
        return $this->redirectToRoute('app_question_index');
    }

    // ✅ CALL GEMINI WITH RETRY
    private function callGemini(string $prompt): ?array
    {
        $models = [
            'gemini-2.0-flash-lite',
            'gemini-2.0-flash',
            'gemini-2.5-flash',
        ];

        foreach ($models as $model) {
            try {
                sleep(1); // small delay to avoid rate limit

                $response = $this->httpClient->request(
                    'POST',
                    "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . $this->geminiApiKey,
                    [
                        'timeout' => 30,
                        'json'    => [
                            'contents' => [
                                ['parts' => [['text' => $prompt]]]
                            ]
                        ]
                    ]
                );

                $data = $response->toArray();
                $raw  = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
                $raw  = preg_replace('/```json|```/', '', $raw);
                $raw  = trim($raw);

                $decoded = json_decode($raw, true);

                if (is_array($decoded) && count($decoded) > 0) {
                    return $decoded; // ✅ success
                }

            } catch (\Exception $e) {
                // Try next model
                continue;
            }
        }

        return null; // all models failed
    }

    #[Route('/generate/ia', name: 'app_question_generate_ia', methods: ['GET', 'POST'])]
    public function generateIa(
        Request $request,
        EntityManagerInterface $em,
        QuizRepository $quizRepository
    ): Response {
        $quizzes            = $quizRepository->findAll();
        $generatedQuestions = [];
        $error              = null;

        if ($request->isMethod('POST')) {
            $topic  = $request->request->get('topic');
            $count  = (int) $request->request->get('count', 5);
            $quizId = $request->request->get('quiz_id');
            $quiz   = $quizId ? $quizRepository->find($quizId) : null;
            $save   = $request->request->get('save');

            $prompt = "Génère $count questions de quiz à choix multiple sur le sujet : \"$topic\".
            Pour chaque question, retourne un JSON valide avec ce format EXACT (tableau JSON):
            [
              {
                \"question\": \"Texte de la question ?\",
                \"choiceA\": \"Réponse A\",
                \"choiceB\": \"Réponse B\",
                \"choiceC\": \"Réponse C\",
                \"correctAnswer\": \"A\"
              }
            ]
            Retourne UNIQUEMENT le tableau JSON, sans explication, sans markdown, sans backticks.";

            $generatedQuestions = $this->callGemini($prompt);

            if ($generatedQuestions === null) {
                $error = '⚠️ API Gemini temporairement indisponible (limite atteinte). Réessayez dans 1 minute.';
                $generatedQuestions = [];
            } elseif ($save && $quiz) {
                // ✅ Save to DB
                foreach ($generatedQuestions as $q) {
                    $question = new Question();
                    $question->setQuestionText($q['question']);
                    $question->setChoiceA($q['choiceA']);
                    $question->setChoiceB($q['choiceB']);
                    $question->setChoiceC($q['choiceC']);
                    $question->setCorrectAnswer($q['correctAnswer']);
                    $question->setQuiz($quiz);
                    $em->persist($question);
                }
                $em->flush();
                $this->addFlash('success', count($generatedQuestions) . ' questions générées et sauvegardées ! 🤖');
                return $this->redirectToRoute('app_question_index');
            }
        }

        return $this->render('question/generate_ia.html.twig', [
            'quizzes'            => $quizzes,
            'generatedQuestions' => $generatedQuestions,
            'error'              => $error,
        ]);
    }
}