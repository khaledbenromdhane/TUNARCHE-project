<?php

namespace App\Entity;

use App\Repository\QuestionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: QuestionRepository::class)]
class Question
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $questionText = null;

    #[ORM\Column(length: 255)]
    private ?string $choiceA = null;

    #[ORM\Column(length: 255)]
    private ?string $choiceB = null;

    #[ORM\Column(length: 255)]
    private ?string $choiceC = null;

    #[ORM\Column(length: 1)]
    private ?string $correctAnswer = null; // A, B or C

    #[ORM\ManyToOne(inversedBy: 'questions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Quiz $quiz = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getQuestionText(): ?string
    {
        return $this->questionText;
    }

    public function setQuestionText(string $questionText): static
    {
        $this->questionText = $questionText;
        return $this;
    }

    public function getChoiceA(): ?string
    {
        return $this->choiceA;
    }

    public function setChoiceA(string $choiceA): static
    {
        $this->choiceA = $choiceA;
        return $this;
    }

    public function getChoiceB(): ?string
    {
        return $this->choiceB;
    }

    public function setChoiceB(string $choiceB): static
    {
        $this->choiceB = $choiceB;
        return $this;
    }

    public function getChoiceC(): ?string
    {
        return $this->choiceC;
    }

    public function setChoiceC(string $choiceC): static
    {
        $this->choiceC = $choiceC;
        return $this;
    }

    public function getCorrectAnswer(): ?string
    {
        return $this->correctAnswer;
    }

    public function setCorrectAnswer(string $correctAnswer): static
    {
        $this->correctAnswer = $correctAnswer;
        return $this;
    }

    public function getQuiz(): ?Quiz
    {
        return $this->quiz;
    }

    public function setQuiz(?Quiz $quiz): static
    {
        $this->quiz = $quiz;
        return $this;
    }
}