<?php

namespace App\Controller\Admin;

use App\Entity\Oeuvre;
use App\Form\OeuvreType;
use App\Repository\GalerieRepository;
use App\Repository\OeuvreRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/admin/oeuvre', name: 'app_admin_oeuvre_')]
class OeuvreController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private OeuvreRepository $oeuvreRepository,
        private GalerieRepository $galerieRepository,
        private SluggerInterface $slugger
    ) {
    }

    private function handleStatutDateVente(Oeuvre $oeuvre): void
    {
        if ($oeuvre->getStatut() === 'vendue') {
            if ($oeuvre->getDateVente() === null) {
                $oeuvre->setDateVente(new \DateTimeImmutable());
            }
        } else {
            $oeuvre->setDateVente(null);
        }
    }

    private function handleOeuvreImage(?object $imageFile, Oeuvre $oeuvre): void
    {
        if (!$imageFile) {
            return;
        }
        $originalName = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
        $safeName = $this->slugger->slug($originalName);
        $ext = $imageFile->guessExtension() ?: $imageFile->getClientOriginalExtension() ?: 'jpg';
        $fileName = $safeName . '-' . uniqid() . '.' . $ext;
        $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/oeuvres';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        try {
            $imageFile->move($uploadDir, $fileName);
            $oeuvre->setImage($fileName);
        } catch (FileException $e) {
            // ignore
        }
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $search = (string) $request->query->get('q', '');
        $sortBy = $request->query->get('sort');
        $sortOrder = (string) $request->query->get('order', 'ASC');
        $filterGalerie = $request->query->get('galerie') !== null && $request->query->get('galerie') !== '' ? (int) $request->query->get('galerie') : null;
        $filterEtat = $request->query->get('etat');

        $oeuvres = $this->oeuvreRepository->searchFilterSort($search, $sortBy, $sortOrder, $filterGalerie, $filterEtat);
        $galeries = $this->galerieRepository->findAllOrdered();

        $isAjax = $request->isXmlHttpRequest()
            || str_contains((string) $request->headers->get('Accept', ''), 'application/json');
        if ($isAjax) {
            $html = $this->renderView('admin/oeuvre/_list_rows.html.twig', ['oeuvres' => $oeuvres]);
            return new JsonResponse(['html' => $html]);
        }

        return $this->render('admin/oeuvre/index.html.twig', [
            'oeuvres' => $oeuvres,
            'galeries' => $galeries,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response|JsonResponse
    {
        $oeuvre = new Oeuvre();
        $form = $this->createForm(OeuvreType::class, $oeuvre);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->handleStatutDateVente($oeuvre);
            $this->handleOeuvreImage($form->get('imageFile')->getData(), $oeuvre);
            $this->em->persist($oeuvre);
            $this->em->flush();
            $this->addFlash('success', 'L\'œuvre a été créée avec succès.');
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse(['success' => true, 'redirect' => $this->generateUrl('app_admin_oeuvre_index')]);
            }
            return $this->redirectToRoute('app_admin_oeuvre_index');
        }

        if ($request->isXmlHttpRequest()) {
            return $this->render('admin/oeuvre/_form_section.html.twig', [
                'oeuvre' => $oeuvre,
                'form' => $form,
                'is_edit' => false,
            ]);
        }
        return $this->render('admin/oeuvre/form.html.twig', [
            'oeuvre' => $oeuvre,
            'form' => $form,
            'is_edit' => false,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Oeuvre $oeuvre): Response|JsonResponse
    {
        $form = $this->createForm(OeuvreType::class, $oeuvre);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->handleStatutDateVente($oeuvre);
            $this->handleOeuvreImage($form->get('imageFile')->getData(), $oeuvre);
            $this->em->flush();
            $this->addFlash('success', 'L\'œuvre a été modifiée avec succès.');
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse(['success' => true, 'redirect' => $this->generateUrl('app_admin_oeuvre_index')]);
            }
            return $this->redirectToRoute('app_admin_oeuvre_index');
        }

        if ($request->isXmlHttpRequest()) {
            return $this->render('admin/oeuvre/_form_section.html.twig', [
                'oeuvre' => $oeuvre,
                'form' => $form,
                'is_edit' => true,
            ]);
        }
        return $this->render('admin/oeuvre/form.html.twig', [
            'oeuvre' => $oeuvre,
            'form' => $form,
            'is_edit' => true,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Oeuvre $oeuvre): Response
    {
        if ($this->isCsrfTokenValid('delete' . $oeuvre->getId(), (string) $request->request->get('_token'))) {
            $this->em->remove($oeuvre);
            $this->em->flush();
            $this->addFlash('success', 'L\'œuvre a été supprimée.');
        }
        return $this->redirectToRoute('app_admin_oeuvre_index');
    }

    #[Route('/export-vendues', name: 'export_vendues', methods: ['GET'])]
    public function exportVendues(): Response
    {
        $oeuvres = $this->oeuvreRepository->findVendues();
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Œuvres vendues');

        $totalRevenus = 0.0;
        foreach ($oeuvres as $o) {
            $totalRevenus += (float) $o->getPrix();
        }
        $nombre = \count($oeuvres);
        $prixMoyen = $nombre > 0 ? $totalRevenus / $nombre : 0.0;

        $row = 1;
        $sheet->setCellValue('A' . $row, 'ID');
        $sheet->setCellValue('B' . $row, 'Titre');
        $sheet->setCellValue('C' . $row, 'Artiste');
        $sheet->setCellValue('D' . $row, 'Galerie');
        $sheet->setCellValue('E' . $row, 'Prix (DT)');
        $sheet->getStyle('A1:E1')->getFont()->setBold(true);
        $sheet->getStyle('A1:E1')->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('E0E0E0');
        $row++;

        foreach ($oeuvres as $oeuvre) {
            $sheet->setCellValue('A' . $row, $oeuvre->getId());
            $sheet->setCellValue('B' . $row, $oeuvre->getTitre());
            $sheet->setCellValue('C' . $row, $oeuvre->getArtiste() ? $oeuvre->getArtiste()->getFullName() : '-');
            $sheet->setCellValue('D' . $row, $oeuvre->getGalerie() ? $oeuvre->getGalerie()->getNom() : '-');
            $sheet->setCellValue('E' . $row, (float) $oeuvre->getPrix());
            $row++;
        }

        $sheet->setCellValue('D' . $row, 'Total revenus (DT)');
        $sheet->setCellValue('E' . $row, $totalRevenus);
        $sheet->getStyle('D' . $row . ':E' . $row)->getFont()->setBold(true);
        $sheet->getStyle('D' . $row . ':E' . $row)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('C8E6C9');
        $row += 2;

        $sheet->setCellValue('A' . $row, 'Statistiques des ventes');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row++;
        $sheet->setCellValue('A' . $row, 'Nombre d\'œuvres vendues');
        $sheet->setCellValue('B' . $row, $nombre);
        $row++;
        $sheet->setCellValue('A' . $row, 'Chiffre d\'affaires total (DT)');
        $sheet->setCellValue('B' . $row, round($totalRevenus, 2));
        $row++;
        $sheet->setCellValue('A' . $row, 'Prix moyen par œuvre (DT)');
        $sheet->setCellValue('B' . $row, round($prixMoyen, 2));
        $row++;

        $statsByGalerie = [];
        foreach ($oeuvres as $oeuvre) {
            $nom = $oeuvre->getGalerie() ? $oeuvre->getGalerie()->getNom() : 'Sans galerie';
            if (!isset($statsByGalerie[$nom])) {
                $statsByGalerie[$nom] = ['count' => 0, 'total' => 0.0];
            }
            $statsByGalerie[$nom]['count']++;
            $statsByGalerie[$nom]['total'] += (float) $oeuvre->getPrix();
        }
        if ($statsByGalerie !== []) {
            $row++;
            $sheet->setCellValue('A' . $row, 'Répartition par galerie');
            $sheet->getStyle('A' . $row)->getFont()->setBold(true);
            $row++;
            $sheet->setCellValue('A' . $row, 'Galerie');
            $sheet->setCellValue('B' . $row, 'Nb œuvres');
            $sheet->setCellValue('C' . $row, 'CA (DT)');
            $sheet->getStyle('A' . $row . ':C' . $row)->getFont()->setBold(true);
            $row++;
            foreach ($statsByGalerie as $nom => $data) {
                $sheet->setCellValue('A' . $row, $nom);
                $sheet->setCellValue('B' . $row, $data['count']);
                $sheet->setCellValue('C' . $row, round($data['total'], 2));
                $row++;
            }
        }

        foreach (range('A', 'E') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $fileName = 'oeuvres-vendues-' . date('Y-m-d-His') . '.xlsx';
        $response = new StreamedResponse(static function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        });
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $fileName . '"');
        $response->headers->set('Cache-Control', 'max-age=0');

        return $response;
    }
}
