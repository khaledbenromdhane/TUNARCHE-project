<?php

namespace App\Entity;

use App\Repository\FormationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: FormationRepository::class)]
class Formation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le nom est obligatoire")]
    private ?string $nomForm = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $dateForm = null;

    #[ORM\Column(length: 100)]
    private ?string $type = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;

    #[ORM\OneToMany(mappedBy: 'formation', targetEntity: Evaluation::class, orphanRemoval: true)]
    private Collection $evaluations;

    #[ORM\OneToMany(mappedBy: 'formation', targetEntity: Quiz::class, orphanRemoval: true)]
    private Collection $quizzes;

    public function __construct()
    {
        $this->evaluations = new ArrayCollection();
        $this->quizzes = new ArrayCollection();
    }

    // --- GETTERS & SETTERS ---

    public function getId(): ?int 
    { 
        return $this->id; 
    }

    public function getNomForm(): ?string { return $this->nomForm; }
    public function setNomForm(string $nomForm): self { $this->nomForm = $nomForm; return $this; }

    public function getDateForm(): ?\DateTimeInterface { return $this->dateForm; }
    public function setDateForm(?\DateTimeInterface $dateForm): self { $this->dateForm = $dateForm; return $this; }

    public function getType(): ?string { return $this->type; }
    public function setType(?string $type): self { $this->type = $type; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): self { $this->description = $description; return $this; }

    /** @return Collection<int, Evaluation> */
    public function getEvaluations(): Collection { return $this->evaluations; }

    /** @return Collection<int, Quiz> */
    public function getQuizzes(): Collection { return $this->quizzes; }

    public function addQuiz(Quiz $quiz): self
    {
        if (!$this->quizzes->contains($quiz)) {
            $this->quizzes->add($quiz);
            $quiz->setFormation($this);
        }
        return $this;
    }

    public function removeQuiz(Quiz $quiz): self
    {
        if ($this->quizzes->removeElement($quiz)) {
            if ($quiz->getFormation() === $this) {
                $quiz->setFormation(null);
            }
        }
        return $this;
    }
}