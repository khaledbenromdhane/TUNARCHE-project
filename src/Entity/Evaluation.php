<?php

namespace App\Entity;

use App\Repository\EvaluationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: EvaluationRepository::class)]
class Evaluation
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le titre est obligatoire")]
    private ?string $titre = null;

    #[ORM\Column]
    #[Assert\NotNull]
    #[Assert\Range(min: 0, max: 5, notInRangeMessage: "La note doit être entre {{ min }} et {{ max }}")]
    private ?int $note = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 10, minMessage: "Commentaire trop court (min 10)")]
    private ?string $commentaire = null;

    #[ORM\ManyToOne(inversedBy: 'evaluations')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: "Veuillez choisir une formation")]
    private ?Formation $formation = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'evaluations')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id_user', nullable: true)]
    private ?User $user = null;

    public function getId(): ?int { return $this->id; }

    public function getTitre(): ?string { return $this->titre; }
    public function setTitre(string $titre): self { $this->titre = $titre; return $this; }

    public function getNote(): ?int { return $this->note; }
    public function setNote(int $note): self { $this->note = $note; return $this; }

    public function getCommentaire(): ?string { return $this->commentaire; }
    public function setCommentaire(string $commentaire): self { $this->commentaire = $commentaire; return $this; }

    public function getFormation(): ?Formation { return $this->formation; }
    public function setFormation(?Formation $formation): self { $this->formation = $formation; return $this; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): self { $this->user = $user; return $this; }
}