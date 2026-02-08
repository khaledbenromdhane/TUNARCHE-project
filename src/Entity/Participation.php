<?php

namespace App\Entity;

use App\Repository\ParticipationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ParticipationRepository::class)]
#[ORM\Table(name: 'participation')]
class Participation
{
    public const STATUTS = [
        'En attente',
        'Confirmée',
        'Annulée',
    ];

    public const MODES_PAIEMENT = [
        'Carte',
        'Cash',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_participation', type: 'integer')]
    private ?int $id = null;

    /**
     * FK vers User — stocké comme int pour l'instant.
     * Quand l'entité User sera prête, remplacez par une relation ManyToOne.
     */
    #[ORM\Column(name: 'id_user', type: 'integer', nullable: true)]
    private ?int $idUser = null;

    #[ORM\ManyToOne(targetEntity: Evenement::class)]
    #[ORM\JoinColumn(name: 'id_evenement', referencedColumnName: 'id_evenement', nullable: false)]
    private ?Evenement $evenement = null;

    #[ORM\Column(name: 'date_participation', type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $dateParticipation = null;

    #[ORM\Column(length: 30)]
    private ?string $statut = null;

    #[ORM\Column(name: 'nbr_participation', type: 'integer')]
    private ?int $nbrParticipation = null;

    #[ORM\Column(name: 'mode_paiement', length: 30, nullable: true)]
    private ?string $modePaiement = null;

    // ─── Getters & Setters ─────────────────────────────────

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIdUser(): ?int
    {
        return $this->idUser;
    }

    public function setIdUser(?int $idUser): static
    {
        $this->idUser = $idUser;
        return $this;
    }

    public function getEvenement(): ?Evenement
    {
        return $this->evenement;
    }

    public function setEvenement(?Evenement $evenement): static
    {
        $this->evenement = $evenement;
        return $this;
    }

    public function getDateParticipation(): ?\DateTimeInterface
    {
        return $this->dateParticipation;
    }

    public function setDateParticipation(?\DateTimeInterface $dateParticipation): static
    {
        $this->dateParticipation = $dateParticipation;
        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(?string $statut): static
    {
        $this->statut = $statut;
        return $this;
    }

    public function getNbrParticipation(): ?int
    {
        return $this->nbrParticipation;
    }

    public function setNbrParticipation(?int $nbrParticipation): static
    {
        $this->nbrParticipation = $nbrParticipation;
        return $this;
    }

    public function getModePaiement(): ?string
    {
        return $this->modePaiement;
    }

    public function setModePaiement(?string $modePaiement): static
    {
        $this->modePaiement = $modePaiement;
        return $this;
    }
}
