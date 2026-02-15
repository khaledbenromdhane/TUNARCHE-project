<?php

namespace App\Service;

use App\Entity\Evenement;
use Stripe\Checkout\Session;
use Stripe\Stripe;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Service for Stripe Checkout integration.
 * Creates a Stripe Checkout Session for paid events when user selects "Carte".
 */
class StripeService
{
    private string $secretKey;
    private UrlGeneratorInterface $urlGenerator;

    public function __construct(string $stripeSecretKey, UrlGeneratorInterface $urlGenerator)
    {
        $this->secretKey = $stripeSecretKey;
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * Create a Stripe Checkout Session for an event participation.
     *
     * @param Evenement $evenement   The event to pay for
     * @param int       $nbrPlaces   Number of reserved places
     * @return Session
     */
    public function createCheckoutSession(Evenement $evenement, int $nbrPlaces): Session
    {
        Stripe::setApiKey($this->secretKey);

        // Get price from event (in euros), convert to cents for Stripe
        $priceEuros = $evenement->getPrix() ?? 10.00;
        $priceInCents = (int) round($priceEuros * 100);

        $session = Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency'     => 'eur',
                    'product_data' => [
                        'name'        => $evenement->getNom(),
                        'description' => sprintf(
                            '%s — %s, %s | %s place(s) × %.2f €',
                            $evenement->getTypeEvenement(),
                            $evenement->getDate()?->format('d/m/Y'),
                            $evenement->getLieu(),
                            $nbrPlaces,
                            $priceEuros
                        ),
                    ],
                    'unit_amount' => $priceInCents,
                ],
                'quantity' => $nbrPlaces,
            ]],
            'mode' => 'payment',
            'success_url' => $this->urlGenerator->generate(
                'app_evenement_stripe_success',
                [],
                UrlGeneratorInterface::ABSOLUTE_URL
            ) . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => $this->urlGenerator->generate(
                'app_evenement_stripe_cancel',
                [],
                UrlGeneratorInterface::ABSOLUTE_URL
            ),
            'metadata' => [
                'id_evenement'      => $evenement->getId(),
                'nbr_participation' => $nbrPlaces,
            ],
        ]);

        return $session;
    }

    /**
     * Retrieve a Checkout Session by ID to verify payment.
     */
    public function retrieveSession(string $sessionId): Session
    {
        Stripe::setApiKey($this->secretKey);
        return Session::retrieve($sessionId);
    }
}
