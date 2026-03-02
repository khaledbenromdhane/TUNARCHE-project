<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'iduser')]
    private ?int $iduser = null;

    #[ORM\Column(length: 100)]
    private ?string $nomuser = null;

    #[ORM\Column(length: 100)]
    private ?string $prenomuser = null;

    #[ORM\Column(length: 50)]
    private ?string $role = 'artist';

    #[ORM\OneToMany(targetEntity: Oeuvre::class, mappedBy: 'artiste')]
    private Collection $oeuvres;

    #[ORM\ManyToMany(targetEntity: Galerie::class, mappedBy: 'artistes')]
    private Collection $galeries;

    public function __construct()
    {
        $this->oeuvres = new ArrayCollection();
        $this->galeries = new ArrayCollection();
    }

    public function getIduser(): ?int
    {
        return $this->iduser;
    }

    public function getNomuser(): ?string
    {
        return $this->nomuser;
    }

    public function setNomuser(string $nomuser): static
    {
        $this->nomuser = $nomuser;
        return $this;
    }

    public function getPrenomuser(): ?string
    {
        return $this->prenomuser;
    }

    public function setPrenomuser(string $prenomuser): static
    {
        $this->prenomuser = $prenomuser;
        return $this;
    }

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setRole(string $role): static
    {
        $this->role = $role;
        return $this;
    }

    public function getNomComplet(): string
    {
        return $this->prenomuser . ' ' . $this->nomuser;
    }

    /**
     * @return Collection<int, Oeuvre>
     */
    public function getOeuvres(): Collection
    {
        return $this->oeuvres;
    }

    /**
     * @return Collection<int, Galerie>
     */
    public function getGaleries(): Collection
    {
        return $this->galeries;
    }

    public function __toString(): string
    {
        return $this->getNomComplet();
    }
}
