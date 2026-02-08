<?php

namespace App\Service;

use App\Entity\Participation;
use App\Repository\EvenementRepository;
use App\Repository\ParticipationRepository;

/**
 * Service layer for Participation.
 * Handles PHP validation (contrôle de saisie) and statistics.
 * Persistence (persist/flush/remove) is handled by the controller via ManagerRegistry.
 */
class ParticipationService
{
    public function __construct(
        private ParticipationRepository $participationRepository,
        private EvenementRepository $evenementRepository
    ) {}

    // ─── READ ──────────────────────────────────────────────

    public function getAll(): array
    {
        return $this->participationRepository->findBy([], ['dateParticipation' => 'DESC']);
    }

    public function getById(int $id): ?Participation
    {
        return $this->participationRepository->find($id);
    }

    public function getByEvenement(int $evenementId): array
    {
        return $this->participationRepository->findByEvenement($evenementId);
    }

    // ─── Statistics ────────────────────────────────────────

    public function countAll(): int
    {
        return $this->participationRepository->countAll();
    }

    public function countConfirmed(): int
    {
        return $this->participationRepository->countConfirmed();
    }

    public function countPending(): int
    {
        return $this->participationRepository->countPending();
    }

    public function countCancelled(): int
    {
        return $this->participationRepository->countCancelled();
    }

    public function sumAllPlaces(): int
    {
        return $this->participationRepository->sumAllPlaces();
    }

    /**
     * Get remaining places for an event.
     */
    public function getPlacesRestantes(int $evenementId): int
    {
        $evenement = $this->evenementRepository->find($evenementId);
        if (!$evenement) {
            return 0;
        }
        $reserved = $this->participationRepository->sumPlacesReservees($evenementId);
        return max(0, $evenement->getNbrParticipant() - $reserved);
    }

    // ─── PHP VALIDATION (Contrôle de saisie) ──────────────

    /**
     * Validate participation form data.
     * Returns an array of field => error message. Empty array = valid.
     *
     * @param array    $data                Form data
     * @param int|null $excludeParticipationId  ID to exclude from capacity check (for updates)
     */
    public function validate(array $data, ?int $excludeParticipationId = null): array
    {
        $errors = [];

        // ── id_evenement ─────────────────────────────
        if (empty($data['id_evenement'] ?? '')) {
            $errors['id_evenement'] = 'L\'événement est obligatoire.';
        } else {
            $evenement = $this->evenementRepository->find((int)$data['id_evenement']);
            if (!$evenement) {
                $errors['id_evenement'] = 'L\'événement sélectionné n\'existe pas.';
            }
        }

        // ── date_participation ───────────────────────
        if (empty($data['date_participation'] ?? '')) {
            $errors['date_participation'] = 'La date de participation est obligatoire.';
        } else {
            $dateObj = \DateTime::createFromFormat('Y-m-d', $data['date_participation']);
            if (!$dateObj || $dateObj->format('Y-m-d') !== $data['date_participation']) {
                $errors['date_participation'] = 'La date n\'est pas valide (format attendu : AAAA-MM-JJ).';
            } else {
                // La date doit être entre aujourd'hui et la date de l'événement
                $today = new \DateTime('today');
                if ($dateObj < $today) {
                    $errors['date_participation'] = 'La date de participation ne peut pas être dans le passé.';
                } elseif (!empty($data['id_evenement']) && empty($errors['id_evenement'])) {
                    $evenementForDate = $this->evenementRepository->find((int)$data['id_evenement']);
                    if ($evenementForDate && $evenementForDate->getDate()) {
                        $eventDate = \DateTime::createFromFormat('Y-m-d', $evenementForDate->getDate()->format('Y-m-d'));
                        if ($dateObj > $eventDate) {
                            $errors['date_participation'] = 'La date de participation ne peut pas dépasser la date de l\'événement (' . $evenementForDate->getDate()->format('d/m/Y') . ').';
                        }
                    }
                }
            }
        }

        // ── nbr_participation (places réservées) ─────
        if (!isset($data['nbr_participation']) || $data['nbr_participation'] === '') {
            $errors['nbr_participation'] = 'Le nombre de places réservées est obligatoire.';
        } elseif (!is_numeric($data['nbr_participation'])) {
            $errors['nbr_participation'] = 'Le nombre de places doit être un nombre entier.';
        } elseif ((int)$data['nbr_participation'] < 1) {
            $errors['nbr_participation'] = 'Le nombre de places doit être au moins 1.';
        } elseif ((int)$data['nbr_participation'] > 100000) {
            $errors['nbr_participation'] = 'Le nombre de places ne peut pas dépasser 100 000.';
        }

        // ── Capacity check: sum of reserved places cannot exceed nbr_participant ──
        if (empty($errors['id_evenement']) && empty($errors['nbr_participation'])) {
            $evenement = $this->evenementRepository->find((int)$data['id_evenement']);
            if ($evenement) {
                if ($excludeParticipationId) {
                    $currentReserved = $this->participationRepository->sumPlacesReserveesExcluding(
                        $evenement->getId(),
                        $excludeParticipationId
                    );
                } else {
                    $currentReserved = $this->participationRepository->sumPlacesReservees($evenement->getId());
                }

                $remaining = $evenement->getNbrParticipant() - $currentReserved;

                if ($remaining <= 0) {
                    $errors['nbr_participation'] = 'Cet événement est complet. Aucune place disponible.';
                } elseif ((int)$data['nbr_participation'] > $remaining) {
                    $errors['nbr_participation'] = 'Il ne reste que ' . $remaining . ' place(s) disponible(s) pour cet événement.';
                }
            }
        }

        // ── statut ───────────────────────────────────
        if (empty($data['statut'] ?? '')) {
            $errors['statut'] = 'Le statut est obligatoire.';
        } elseif (!in_array($data['statut'], Participation::STATUTS, true)) {
            $errors['statut'] = 'Statut invalide. Les statuts autorisés sont : ' . implode(', ', Participation::STATUTS) . '.';
        }

        // ── Statut logic: if event is paid → must be confirmed to pay ──
        // (statut is set based on whether the event requires payment)

        // ── mode_paiement ────────────────────────────
        if (!empty($data['id_evenement']) && empty($errors['id_evenement'])) {
            $evenement = $evenement ?? $this->evenementRepository->find((int)$data['id_evenement']);
            if ($evenement && $evenement->isPaiement()) {
                // Event requires payment → mode_paiement is required
                if (empty($data['mode_paiement'] ?? '')) {
                    $errors['mode_paiement'] = 'Le mode de paiement est obligatoire pour un événement payant.';
                } elseif (!in_array($data['mode_paiement'], Participation::MODES_PAIEMENT, true)) {
                    $errors['mode_paiement'] = 'Mode de paiement invalide. Les modes autorisés sont : ' . implode(', ', Participation::MODES_PAIEMENT) . '.';
                }
            }
        }

        return $errors;
    }
}
