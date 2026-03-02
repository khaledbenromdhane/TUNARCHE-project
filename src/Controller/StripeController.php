<?php

namespace App\Controller;

use App\Entity\Oeuvre;
use App\Repository\OeuvreRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Stripe\StripeClient;
use Stripe\Exception\ApiErrorException;

/**
 * Intégration Stripe : création de session Checkout et pages succès/annule.
 */
#[Route('/front', name: 'app_stripe_')]
class StripeController extends AbstractController
{
    public function __construct(
        private string $stripeSecretKey,
        private OeuvreRepository $oeuvreRepository,
        private UrlGeneratorInterface $urlGenerator,
        private EntityManagerInterface $em
    ) {
    }

    /**
     * Crée une session Stripe Checkout pour l'œuvre et redirige vers Stripe.
     */
    #[Route('/oeuvre/{id}/acheter', name: 'acheter', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function acheter(int $id, Request $request): Response
    {
        $oeuvre = $this->oeuvreRepository->find($id);
        if (!$oeuvre) {
            throw $this->createNotFoundException('Œuvre non trouvée.');
        }
        if (!$oeuvre->isDisponible()) {
            $this->addFlash('error', 'Cette œuvre n\'est pas disponible à la vente.');
            return $this->redirectToRoute('app_front_oeuvre_detail', ['id' => $id]);
        }

        $secretKey = $this->getParameter('stripe_secret_key');
        if (empty($secretKey) || str_contains((string) $secretKey, 'xxx')) {
            $this->addFlash('error', 'Paiement non configuré : ajoutez vos clés Stripe (STRIPE_SECRET_KEY) dans le fichier .env. Récupérez-les sur https://dashboard.stripe.com/apikeys');
            return $this->redirectToRoute('app_front_oeuvre_detail', ['id' => $id]);
        }

        try {
            $stripe = new StripeClient($secretKey);
            $prix = (float) $oeuvre->getPrix();
            // Stripe : montant en centimes pour EUR (devise compatible par défaut avec tous les comptes Stripe)
            $amountCents = (int) round($prix * 100);
            if ($amountCents < 50) {
                $amountCents = 50; // minimum 0,50 EUR
            }

            $successUrl = $this->urlGenerator->generate('app_stripe_succes', [
                'id' => $oeuvre->getId(),
                'session_id' => '{CHECKOUT_SESSION_ID}',
            ], UrlGeneratorInterface::ABSOLUTE_URL);
            $cancelUrl = $this->urlGenerator->generate('app_stripe_annule', ['id' => $oeuvre->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

            $productData = [
                'name' => $oeuvre->getTitre(),
                'description' => $oeuvre->getDescription() ? mb_substr(strip_tags($oeuvre->getDescription()), 0, 500) : null,
            ];
            if ($oeuvre->getImage()) {
                $productData['images'] = [$request->getSchemeAndHttpHost() . '/uploads/oeuvres/' . $oeuvre->getImage()];
            }

            $session = $stripe->checkout->sessions->create([
                'payment_method_types' => ['card'],
                'line_items' => [
                    [
                        'price_data' => [
                            'currency' => 'eur',
                            'product_data' => $productData,
                            'unit_amount' => $amountCents,
                        ],
                        'quantity' => 1,
                    ],
                ],
                'mode' => 'payment',
                'success_url' => $successUrl,
                'cancel_url' => $cancelUrl,
                'metadata' => [
                    'oeuvre_id' => (string) $oeuvre->getId(),
                ],
                'locale' => 'fr',
            ]);

            if (!empty($session->url)) {
                return $this->redirect($session->url, Response::HTTP_SEE_OTHER);
            }
            throw new \RuntimeException('Stripe n\'a pas renvoyé d\'URL de paiement.');
        } catch (ApiErrorException $e) {
            $this->addFlash('error', 'Erreur Stripe : ' . $e->getMessage());
            return $this->redirectToRoute('app_front_oeuvre_detail', ['id' => $id]);
        }
    }

    /**
     * Page affichée après paiement réussi (retour Stripe).
     */
    #[Route('/paiement/succes', name: 'succes', methods: ['GET'])]
    public function succes(Request $request): Response
    {
        $id = (int) $request->query->get('id');
        $sessionId = $request->query->get('session_id');
        $oeuvre = $id ? $this->oeuvreRepository->find($id) : null;
        if ($oeuvre instanceof Oeuvre && $oeuvre->isDisponible()) {
            $oeuvre->setStatut(Oeuvre::STATUT_VENDUE);
            $oeuvre->setDateVente(new \DateTimeImmutable());
            $this->em->flush();
        }
        return $this->render('front/paiement_succes.html.twig', [
            'oeuvre' => $oeuvre,
            'session_id' => $sessionId,
        ]);
    }

    /**
     * Page affichée si l'utilisateur annule le paiement sur Stripe.
     */
    #[Route('/paiement/annule/{id}', name: 'annule', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function annule(int $id): Response
    {
        $oeuvre = $this->oeuvreRepository->find($id);
        return $this->render('front/paiement_annule.html.twig', [
            'oeuvre' => $oeuvre,
        ]);
    }
}
