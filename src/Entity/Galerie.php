<?php

namespace App\Entity;

use App\Repository\GalerieRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: GalerieRepository::class)]
#[ORM\Table(name: 'galerie')]
class Galerie
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_galerie')]
    private ?int $idGalerie = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'La catégorie ne peut pas être vide.')]
    #[Assert\Length(min: 3, max: 100, minMessage: 'La catégorie doit contenir au moins {{ limit }} caractères.', maxMessage: 'La catégorie ne peut pas dépasser {{ limit }} caractères.')]
    private ?string $categorie = null;

    #[ORM\Column(length: 150)]
    #[Assert\NotBlank(message: 'Le nom de la galerie ne peut pas être vide.')]
    #[Assert\Length(min: 3, max: 150, minMessage: 'Le nom doit contenir au moins {{ limit }} caractères.', maxMessage: 'Le nom ne peut pas dépasser {{ limit }} caractères.')]
    private ?string $nom = null;

    #[ORM\Column(name: 'nb_oeuvres_dispo')]
    #[Assert\NotBlank(message: 'Le nombre d\'œuvres disponibles ne peut pas être vide.')]
    #[Assert\Type(type: 'integer', message: 'Le nombre d\'œuvres doit être un entier.')]
    #[Assert\PositiveOrZero(message: 'Le nombre d\'œuvres doit être positif ou zéro.')]
    private ?int $nbOeuvresDispo = 0;

    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: 'galeries')]
    #[ORM\JoinTable(name: 'galerie_artiste')]
    #[ORM\JoinColumn(name: 'galerie_id_galerie', referencedColumnName: 'id_galerie')]
    #[ORM\InverseJoinColumn(name: 'user_iduser', referencedColumnName: 'iduser')]
    private Collection $artistes;

    #[ORM\Column(name: 'nb_employes')]
    #[Assert\NotBlank(message: 'Le nombre d\'employés ne peut pas être vide.')]
    #[Assert\Type(type: 'integer', message: 'Le nombre d\'employés doit être un entier.')]
    #[Assert\PositiveOrZero(message: 'Le nombre d\'employés doit être positif ou zéro.')]
    private ?int $nbEmployes = 0;

    #[ORM\OneToMany(targetEntity: Oeuvre::class, mappedBy: 'galerie')]
    private Collection $oeuvres;

    public function __construct()
    {
        $this->artistes = new ArrayCollection();
        $this->oeuvres = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->nom ?? '';
    }

    public function getIdGalerie(): ?int
    {
        return $this->idGalerie;
    }

    public function getCategorie(): ?string
    {
        return $this->categorie;
    }

    public function setCategorie(string $categorie): static
    {
        $this->categorie = $categorie;
        return $this;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;
        return $this;
    }

    public function getNbOeuvresDispo(): ?int
    {
        return $this->nbOeuvresDispo;
    }

    public function setNbOeuvresDispo(int $nbOeuvresDispo): static
    {
        $this->nbOeuvresDispo = $nbOeuvresDispo;
        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getArtistes(): Collection
    {
        return $this->artistes;
    }

    public function addArtiste(User $artiste): static
    {
        if (!$this->artistes->contains($artiste)) {
            $this->artistes->add($artiste);
        }
        return $this;
    }

    public function removeArtiste(User $artiste): static
    {
        $this->artistes->removeElement($artiste);
        return $this;
    }

    public function getNbEmployes(): ?int
    {
        return $this->nbEmployes;
    }

    public function setNbEmployes(int $nbEmployes): static
    {
        $this->nbEmployes = $nbEmployes;
        return $this;
    }

    /**
     * @return Collection<int, Oeuvre>
     */
    public function getOeuvres(): Collection
    {
        return $this->oeuvres;
    }

    public function addOeuvre(Oeuvre $oeuvre): static
    {
        if (!$this->oeuvres->contains($oeuvre)) {
            $this->oeuvres->add($oeuvre);
            $oeuvre->setGalerie($this);
        }
        return $this;
    }

    public function removeOeuvre(Oeuvre $oeuvre): static
    {
        if ($this->oeuvres->removeElement($oeuvre)) {
            if ($oeuvre->getGalerie() === $this) {
                $oeuvre->setGalerie(null);
            }
        }
        return $this;
    }
}
