<?php

namespace App\Service;

use App\Entity\Evenement;
use App\Repository\EvenementRepository;

/**
 * Service layer for Evenement.
 * Handles PHP validation (contrôle de saisie) and statistics.
 * Persistence (persist/flush/remove) is handled by the controller via ManagerRegistry.
 */
class EvenementService
{
    public function __construct(
        private EvenementRepository $evenementRepository
    ) {}

    // ─── CRUD ──────────────────────────────────────────────

    /**
     * Get all events ordered by date DESC.
     */
    public function getAll(): array
    {
        return $this->evenementRepository->findBy([], ['date' => 'DESC']);
    }

    /**
     * Get a single event by ID.
     */
    public function getById(int $id): ?Evenement
    {
        return $this->evenementRepository->find($id);
    }

    // ─── Statistics ────────────────────────────────────────

    public function countAll(): int
    {
        return count($this->evenementRepository->findAll());
    }

    public function countUpcoming(): int
    {
        return count($this->evenementRepository->findUpcoming());
    }

    public function countPaid(): int
    {
        return $this->evenementRepository->countPaid();
    }

    public function sumParticipants(): int
    {
        return $this->evenementRepository->sumParticipants();
    }

    // ─── PHP VALIDATION (Contrôle de saisie) ──────────────

    /**
     * Validate event form data.
     * Returns an array of field => error message. Empty array = valid.
     * @param bool $skipDateValidation Skip date validation (for updates in back office)
     */
    public function validate(array $data, bool $skipDateValidation = false): array
    {
        $errors = [];

        // ── Nom ──────────────────────────────────────
        if (empty(trim($data['nom'] ?? ''))) {
            $errors['nom'] = 'Le nom de l\'événement est obligatoire.';
        } elseif (mb_strlen(trim($data['nom'])) < 3) {
            $errors['nom'] = 'Le nom doit contenir au moins 3 caractères.';
        } elseif (mb_strlen(trim($data['nom'])) > 255) {
            $errors['nom'] = 'Le nom ne doit pas dépasser 255 caractères.';
        } elseif (!preg_match('/^[a-zA-ZÀ-ÿ0-9\s\-\'\&\.\,]+$/u', trim($data['nom']))) {
            $errors['nom'] = 'Le nom contient des caractères non autorisés.';
        }

        // ── Type événement ───────────────────────────
        if (empty($data['type_evenement'] ?? '')) {
            $errors['type_evenement'] = 'Le type d\'événement est obligatoire.';
        } elseif (!in_array($data['type_evenement'], Evenement::TYPES, true)) {
            $errors['type_evenement'] = 'Type d\'événement invalide. Les types autorisés sont : ' . implode(', ', Evenement::TYPES) . '.';
        }

        // ── Nombre de participants ───────────────────
        if (!isset($data['nbr_participant']) || $data['nbr_participant'] === '') {
            $errors['nbr_participant'] = 'Le nombre de participants est obligatoire.';
        } elseif (!is_numeric($data['nbr_participant'])) {
            $errors['nbr_participant'] = 'Le nombre de participants doit être un nombre entier.';
        } elseif ((int)$data['nbr_participant'] < 1) {
            $errors['nbr_participant'] = 'Le nombre de participants doit être au moins 1.';
        } elseif ((int)$data['nbr_participant'] > 100000) {
            $errors['nbr_participant'] = 'Le nombre de participants ne peut pas dépasser 100 000.';
        }

        // ── Date ─────────────────────────────────────
        if (!$skipDateValidation) {
            if (empty($data['date'] ?? '')) {
                $errors['date'] = 'La date de l\'événement est obligatoire.';
            } else {
                $dateObj = \DateTime::createFromFormat('Y-m-d', $data['date']);
                if (!$dateObj || $dateObj->format('Y-m-d') !== $data['date']) {
                    $errors['date'] = 'La date n\'est pas valide (format attendu : AAAA-MM-JJ).';
                } elseif ($dateObj < new \DateTime('today')) {
                    $errors['date'] = 'La date de l\'événement doit être aujourd\'hui ou dans le futur.';
                }
            }
        }

        // ── Heure ────────────────────────────────────
        if (empty($data['heure'] ?? '')) {
            $errors['heure'] = 'L\'heure de l\'événement est obligatoire.';
        } else {
            $heureObj = \DateTime::createFromFormat('H:i', $data['heure']);
            if (!$heureObj || $heureObj->format('H:i') !== $data['heure']) {
                $errors['heure'] = 'L\'heure n\'est pas valide (format attendu : HH:MM).';
            }
        }

        // ── Lieu ─────────────────────────────────────
        if (empty(trim($data['lieu'] ?? ''))) {
            $errors['lieu'] = 'Le lieu de l\'événement est obligatoire.';
        } elseif (mb_strlen(trim($data['lieu'])) < 3) {
            $errors['lieu'] = 'Le lieu doit contenir au moins 3 caractères.';
        } elseif (mb_strlen(trim($data['lieu'])) > 255) {
            $errors['lieu'] = 'Le lieu ne doit pas dépasser 255 caractères.';
        }

        // ── Description ──────────────────────────────
        if (empty(trim($data['description'] ?? ''))) {
            $errors['description'] = 'La description est obligatoire.';
        } elseif (mb_strlen(trim($data['description'])) < 10) {
            $errors['description'] = 'La description doit contenir au moins 10 caractères.';
        } elseif (mb_strlen(trim($data['description'])) > 2000) {
            $errors['description'] = 'La description ne doit pas dépasser 2000 caractères.';
        }

        // ── Paiement (boolean – optional, defaults to false) ──
        // No validation needed, it's a checkbox (true/false)

        // ── Prix (required if paiement is true) ──────────────
        if (!empty($data['paiement'])) {
            if (!isset($data['prix']) || $data['prix'] === '') {
                $errors['prix'] = 'Le prix est obligatoire pour un événement payant.';
            } elseif (!is_numeric($data['prix'])) {
                $errors['prix'] = 'Le prix doit être un nombre valide.';
            } elseif ((float)$data['prix'] < 0.01) {
                $errors['prix'] = 'Le prix doit être supérieur à 0.';
            } elseif ((float)$data['prix'] > 99999) {
                $errors['prix'] = 'Le prix ne peut pas dépasser 99 999 €.';
            }
        }

        // ── Image (optional file upload) ──────────────────
        if (!empty($data['image_file'])) {
            $file = $data['image_file'];
            $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $allowedExts  = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $maxSize      = 780 * 1024; // 780 KB

            if ($file instanceof \Symfony\Component\HttpFoundation\File\UploadedFile) {
                if (!in_array($file->getMimeType(), $allowedMimes, true)) {
                    $errors['image'] = 'Format d\'image non autorisé. Formats acceptés : JPG, PNG, GIF, WEBP.';
                } elseif (!in_array(strtolower($file->getClientOriginalExtension()), $allowedExts, true)) {
                    $errors['image'] = 'Extension de fichier non autorisée.';
                } elseif ($file->getSize() > $maxSize) {
                    $errors['image'] = 'L\'image ne doit pas dépasser 780 Ko.';
                }
            } else {
                $errors['image'] = 'Fichier image invalide.';
            }
        }

        return $errors;
    }

}
