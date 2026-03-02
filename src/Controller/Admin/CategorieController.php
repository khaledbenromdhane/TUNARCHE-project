<?php

namespace App\Controller\Admin;

use App\Entity\Galerie;
use App\Form\GalerieType;
use App\Repository\GalerieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/galerie', name: 'app_admin_galerie_')]
class CategorieController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private GalerieRepository $galerieRepository
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $search = (string) $request->query->get('q', '');
        $sortBy = $request->query->get('sort');
        $sortOrder = (string) $request->query->get('order', 'ASC');
        $filterCategorie = $request->query->get('categorie');

        $galeries = $this->galerieRepository->searchFilterSort($search, $sortBy, $sortOrder, $filterCategorie);
        $categories = $this->galerieRepository->findDistinctCategories();

        if ($request->isXmlHttpRequest()) {
            $html = $this->renderView('admin/categorie/_list_rows.html.twig', ['galeries' => $galeries]);
            return new JsonResponse(['html' => $html]);
        }

        return $this->render('admin/categorie/index.html.twig', [
            'galeries' => $galeries,
            'categories' => $categories,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response|JsonResponse
    {
        $galerie = new Galerie();
        $form = $this->createForm(GalerieType::class, $galerie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($galerie);
            $this->em->flush();
            $this->addFlash('success', 'La galerie a été créée avec succès.');
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse(['success' => true, 'redirect' => $this->generateUrl('app_admin_galerie_index')]);
            }
            return $this->redirectToRoute('app_admin_galerie_index');
        }

        if ($request->isXmlHttpRequest()) {
            return $this->render('admin/categorie/_form_section.html.twig', [
                'galerie' => $galerie,
                'form' => $form,
                'is_edit' => false,
            ]);
        }
        return $this->render('admin/categorie/form.html.twig', [
            'galerie' => $galerie,
            'form' => $form,
            'is_edit' => false,
        ]);
    }

    #[Route('/{idGalerie}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, int $idGalerie): Response|JsonResponse
    {
        $galerie = $this->galerieRepository->find($idGalerie);
        if (!$galerie) {
            throw $this->createNotFoundException('Galerie non trouvée');
        }
        $form = $this->createForm(GalerieType::class, $galerie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();
            $this->addFlash('success', 'La galerie a été modifiée avec succès.');
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse(['success' => true, 'redirect' => $this->generateUrl('app_admin_galerie_index')]);
            }
            return $this->redirectToRoute('app_admin_galerie_index');
        }

        if ($request->isXmlHttpRequest()) {
            return $this->render('admin/categorie/_form_section.html.twig', [
                'galerie' => $galerie,
                'form' => $form,
                'is_edit' => true,
            ]);
        }
        return $this->render('admin/categorie/form.html.twig', [
            'galerie' => $galerie,
            'form' => $form,
            'is_edit' => true,
        ]);
    }

    #[Route('/{idGalerie}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, int $idGalerie): Response
    {
        $galerie = $this->galerieRepository->find($idGalerie);
        if (!$galerie) {
            throw $this->createNotFoundException('Galerie non trouvée');
        }
        if ($this->isCsrfTokenValid('delete' . $galerie->getIdGalerie(), (string) $request->request->get('_token'))) {
            $this->em->remove($galerie);
            $this->em->flush();
            $this->addFlash('success', 'La galerie a été supprimée.');
        }
        return $this->redirectToRoute('app_admin_galerie_index');
    }
}
