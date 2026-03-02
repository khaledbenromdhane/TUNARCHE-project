<?php

namespace App\Controller;

use App\Entity\Formation;
use App\Entity\Evaluation;
use App\Form\FormationType;
use App\Repository\FormationRepository;
use App\Service\MailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/formation')]
final class FormationController extends AbstractController
{
    #[Route(name: 'app_formation_index', methods: ['GET'])]
    public function index(Request $request, FormationRepository $repo): Response
    {
        $formations = $repo->findBySearchAndFilter(
            $request->query->get('search'),
            $request->query->get('type'),
            $request->query->get('sort', 'f.id'),
            $request->query->get('direction', 'ASC')
        );

        return $this->render('formation/index.html.twig', ['formations' => $formations]);
    }

    #[Route('/list', name: 'app_formation_front')]
    public function indexFront(FormationRepository $repo): Response
    {
        return $this->render('front/formation/index.html.twig', [
            'formations' => $repo->findAll()
        ]);
    }

    #[Route('/new', name: 'app_formation_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, MailService $mailService): Response
    {
        $formation = new Formation();
        $form = $this->createForm(FormationType::class, $formation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $formation->setUser($this->getUser());
            $entityManager->persist($formation);
            $entityManager->flush();

            // ✅ EMAIL — nouvelle formation créée
            $mailService->sendEmail(
                'sizzarga.business@gmail.com',
                'Administrateur',
                '🎓 Nouvelle Formation Ajoutée !',
                "
                <div style='font-family: Arial, sans-serif; padding: 20px;'>
                    <h2 style='color: #2c3e50;'>Nouvelle formation disponible</h2>
                    <hr>
                    <p><strong>Nom :</strong> {$formation->getNomForm()}</p>
                    <p><strong>Type :</strong> {$formation->getType()}</p>
                    <p><strong>Date :</strong> {$formation->getDateForm()->format('d/m/Y')}</p>
                    <p><strong>Description :</strong> {$formation->getDescription()}</p>
                    <hr>
                    <p style='color: #888; font-size: 12px;'>Plateforme de Formation Professionnelle</p>
                </div>
                "
            );

            $this->addFlash('success', 'Formation créée avec succès ! Une notification a été envoyée. ✅');
            return $this->redirectToRoute('app_admin_dashboard');
        }

        return $this->render('formation/new.html.twig', [
            'formation' => $formation,
            'form'      => $form,
        ], new Response(null, $form->isSubmitted() ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK));
    }

    #[Route('/{id}/edit', name: 'app_formation_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Formation $formation, EntityManagerInterface $entityManager, MailService $mailService): Response
    {
        $form = $this->createForm(FormationType::class, $formation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            // ✅ EMAIL — formation modifiée
            $mailService->sendEmail(
                'sizzarga.business@gmail.com',
                'Administrateur',
                '✏️ Formation Modifiée',
                "
                <div style='font-family: Arial, sans-serif; padding: 20px;'>
                    <h2 style='color: #e67e22;'>Formation mise à jour</h2>
                    <hr>
                    <p><strong>Nom :</strong> {$formation->getNomForm()}</p>
                    <p><strong>Type :</strong> {$formation->getType()}</p>
                    <p><strong>Date :</strong> {$formation->getDateForm()->format('d/m/Y')}</p>
                    <hr>
                    <p style='color: #888; font-size: 12px;'>Plateforme de Formation Professionnelle</p>
                </div>
                "
            );

            $this->addFlash('success', 'Modifications enregistrées. ✅');
            return $this->redirectToRoute('app_admin_dashboard');
        }

        return $this->render('formation/edit.html.twig', [
            'formation' => $formation,
            'form'      => $form,
        ], new Response(null, $form->isSubmitted() ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK));
    }

    #[Route('/{id}', name: 'app_formation_delete', methods: ['POST'])]
    public function delete(Request $request, Formation $formation, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $formation->getId(), $request->request->get('_token'))) {
            $entityManager->remove($formation);
            $entityManager->flush();
            $this->addFlash('warning', 'Formation supprimée.');
        }

        return $this->redirectToRoute('app_admin_dashboard');
    }

    #[Route('/evaluer/{id}', name: 'app_formation_evaluer', methods: ['POST'])]
    public function evaluer(Request $request, Formation $formation, EntityManagerInterface $em): Response
    {
        $note        = $request->request->get('note');
        $commentaire = $request->request->get('commentaire');

        if ($note && $commentaire) {
            $evaluation = new Evaluation();
            $evaluation->setNote((int) $note);
            $evaluation->setCommentaire($commentaire);
            $evaluation->setFormation($formation);
            $evaluation->setTitre(mb_strimwidth($commentaire, 0, 20, '...'));
            $evaluation->setUser($this->getUser());

            $em->persist($evaluation);
            $em->flush();

            $this->addFlash('success', 'Merci pour votre avis !');
        } else {
            $this->addFlash('danger', 'Veuillez remplir la note et le commentaire.');
        }

        return $this->redirectToRoute('app_formation_front');
    }
}