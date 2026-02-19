<?php

namespace App\Entity;

use App\Repository\QuizRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: QuizRepository::class)]
class Quiz
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\ManyToOne(targetEntity: Formation::class, inversedBy: 'quizzes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Formation $formation = null;

    #[ORM\OneToMany(mappedBy: 'quiz', targetEntity: Question::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $questions;

    #[ORM\OneToMany(mappedBy: 'quiz', targetEntity: Resultat::class, cascade: ['remove'], orphanRemoval: true)]
    private Collection $resultats;

    public function __construct()
    {
        $this->questions = new ArrayCollection();
        $this->resultats = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getTitle(): ?string { return $this->title; }
    public function setTitle(string $title): self { $this->title = $title; return $this; }

    public function getFormation(): ?Formation { return $this->formation; }
    public function setFormation(?Formation $formation): self { $this->formation = $formation; return $this; }

    /** @return Collection<int, Question> */
    public function getQuestions(): Collection { return $this->questions; }

    public function addQuestion(Question $question): self
    {
        if (!$this->questions->contains($question)) {
            $this->questions->add($question);
            $question->setQuiz($this);
        }
        return $this;
    }

    public function removeQuestion(Question $question): self
    {
        if ($this->questions->removeElement($question)) {
            if ($question->getQuiz() === $this) {
                $question->setQuiz(null);
            }
        }
        return $this;
    }

    /** @return Collection<int, Resultat> */
    public function getResultats(): Collection { return $this->resultats; }

    public function addResultat(Resultat $resultat): self
    {
        if (!$this->resultats->contains($resultat)) {
            $this->resultats->add($resultat);
            $resultat->setQuiz($this);
        }
        return $this;
    }

    public function removeResultat(Resultat $resultat): self
    {
        if ($this->resultats->removeElement($resultat)) {
            if ($resultat->getQuiz() === $this) {
                $resultat->setQuiz(null);
            }
        }
        return $this;
    }
}