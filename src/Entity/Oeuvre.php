<?php

namespace App\Entity;

use App\Repository\OeuvreRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: OeuvreRepository::class)]
#[ORM\Table(name: 'oeuvre')]
class Oeuvre
{
    public const ETAT_NEUVE = 'neuve';
    public const ETAT_DEFECTUEUSE = 'défectueuse';
    public const ETATS = [self::ETAT_NEUVE, self::ETAT_DEFECTUEUSE];

    public const STATUT_DISPONIBLE = 'disponible';
    public const STATUT_VENDUE = 'vendue';
    public const STATUTS = [self::STATUT_DISPONIBLE, self::STATUT_VENDUE];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'id_artiste', referencedColumnName: 'iduser')]
    #[Assert\NotNull(message: 'L\'artiste auteur ne peut pas être vide.')]
    private ?User $artiste = null;

    #[ORM\ManyToOne(targetEntity: Galerie::class)]
    #[ORM\JoinColumn(name: 'id_galerie', referencedColumnName: 'id_galerie')]
    #[Assert\NotNull(message: 'La galerie ne peut pas être vide.')]
    private ?Galerie $galerie = null;

    #[ORM\Column(length: 200)]
    #[Assert\NotBlank(message: 'Le titre ne peut pas être vide.')]
    #[Assert\Length(min: 3, max: 200, minMessage: 'Le titre doit contenir au moins {{ limit }} caractères.', maxMessage: 'Le titre ne peut pas dépasser {{ limit }} caractères.')]
    private ?string $titre = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\NotBlank(message: 'Le prix ne peut pas être vide.')]
    #[Assert\Type(type: 'numeric', message: 'Le prix doit être un nombre.')]
    #[Assert\PositiveOrZero(message: 'Le prix doit être positif ou zéro.')]
    private ?string $prix = null;

    #[ORM\Column(length: 20)]
    #[Assert\NotBlank(message: 'L\'état ne peut pas être vide.')]
    #[Assert\Choice(choices: self::ETATS, message: 'L\'état doit être « neuve » ou « défectueuse ».')]
    private ?string $etat = self::ETAT_NEUVE;

    #[ORM\Column(type: Types::SMALLINT, nullable: false)]
    #[Assert\NotNull(message: 'L\'année de réalisation ne peut pas être vide.')]
    #[Assert\Type(type: 'integer', message: 'L\'année doit être un nombre entier.')]
    #[Assert\Range(min: 1000, max: 9999, notInRangeMessage: 'L\'année doit être entre {{ min }} et {{ max }}.')]
    #[Assert\Callback(callback: [self::class, 'validateAnneeCourante'])]
    private ?int $anneeRealisation = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(max: 5000, maxMessage: 'La description ne peut pas dépasser {{ limit }} caractères.')]
    #[Assert\Callback(callback: [self::class, 'validateDescription'])]
    private ?string $description = null;

    #[ORM\Column(length: 20, options: ['default' => 'disponible'])]
    #[Assert\Choice(choices: self::STATUTS, message: 'Le statut doit être « disponible » ou « vendue ».')]
    private string $statut = self::STATUT_DISPONIBLE;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $dateVente = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getArtiste(): ?User
    {
        return $this->artiste;
    }

    public function setArtiste(?User $artiste): static
    {
        $this->artiste = $artiste;
        return $this;
    }

    public function getGalerie(): ?Galerie
    {
        return $this->galerie;
    }

    public function setGalerie(?Galerie $galerie): static
    {
        $this->galerie = $galerie;
        return $this;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): static
    {
        $this->titre = $titre;
        return $this;
    }

    public function getPrix(): ?string
    {
        return $this->prix;
    }

    public function setPrix(string $prix): static
    {
        $this->prix = $prix;
        return $this;
    }

    public function getEtat(): ?string
    {
        return $this->etat;
    }

    public function setEtat(string $etat): static
    {
        $this->etat = $etat;
        return $this;
    }

    public function getAnneeRealisation(): ?int
    {
        return $this->anneeRealisation;
    }

    public function setAnneeRealisation(int $anneeRealisation): static
    {
        $this->anneeRealisation = $anneeRealisation;
        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): static
    {
        $this->image = $image;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getStatut(): string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;
        return $this;
    }

    public function isDisponible(): bool
    {
        return $this->statut === self::STATUT_DISPONIBLE;
    }

    public function isVendue(): bool
    {
        return $this->statut === self::STATUT_VENDUE;
    }

    public function getDateVente(): ?\DateTimeImmutable
    {
        return $this->dateVente;
    }

    public function setDateVente(?\DateTimeImmutable $dateVente): static
    {
        $this->dateVente = $dateVente;
        return $this;
    }

    public static function validateAnneeCourante(?int $value, ExecutionContextInterface $context): void
    {
        if ($value === null) {
            return;
        }
        $anneeCourante = (int) date('Y');
        if ($value > $anneeCourante) {
            $context->buildViolation('L\'année ne doit pas dépasser l\'année actuelle ({{ annee }}).')
                ->setParameter('{{ annee }}', (string) $anneeCourante)
                ->addViolation();
        }
    }

    public static function validateDescription(?string $value, ExecutionContextInterface $context): void
    {
        if ($value === null || $value === '') {
            return;
        }
        if (\strlen($value) < 3) {
            $context->buildViolation('La description doit contenir au moins 3 caractères si elle est renseignée.')
                ->addViolation();
        }
    }
}
