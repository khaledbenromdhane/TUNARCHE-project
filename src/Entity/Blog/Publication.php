<?php

namespace App\Entity\Blog;

use App\Entity\User;
use App\Repository\Blog\PublicationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PublicationRepository::class)]
#[ORM\Table(name: 'publication')]
class Publication
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_publication', type: Types::INTEGER)]
    private ?int $idPublication = null;

    #[ORM\Column(name: 'date_act', type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateAct = null;

    #[ORM\Column(length: 255)]
    private ?string $description = null;

    #[ORM\Column(length: 255)]
    private ?string $titre = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $slug = null;

    #[ORM\Column(length: 255)]
    private ?string $image = null;

    #[ORM\Column(name: 'image_analysis', type: Types::TEXT, nullable: true)]
    private ?string $imageAnalysis = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'id_user', referencedColumnName: 'id_user', nullable: true)]
    private ?User $user = null;

    #[ORM\Column(name: 'nb_likes', type: Types::INTEGER, nullable: true)]
    private ?int $nbLikes = null;

    #[ORM\Column(name: 'nb_dislikes', type: Types::INTEGER, nullable: true)]
    private ?int $nbDislikes = null;

    #[ORM\OneToMany(targetEntity: PublicationReaction::class, mappedBy: 'publication', cascade: ['remove'])]
    private Collection $reactions;

    #[ORM\OneToMany(targetEntity: Commentaire::class, mappedBy: 'publication', cascade: ['remove'])]
    private Collection $commentaires;

    public function __construct()
    {
        $this->commentaires = new ArrayCollection();
        $this->reactions = new ArrayCollection();
        $this->nbLikes = 0;
        $this->nbDislikes = 0;
        $this->dateAct = new \DateTime();
    }

    public function getIdPublication(): ?int
    {
        return $this->idPublication;
    }

    public function getDateAct(): ?\DateTimeInterface
    {
        return $this->dateAct;
    }

    public function setDateAct(\DateTimeInterface $dateAct): static
    {
        $this->dateAct = $dateAct;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): static
    {
        $this->titre = $titre;
        
        // Auto-generate slug
        $slugger = new AsciiSlugger();
        $this->slug = strtolower($slugger->slug($titre));

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(string $image): static
    {
        $this->image = $image;

        return $this;
    }

    public function getImageAnalysis(): ?string
    {
        return $this->imageAnalysis;
    }

    public function setImageAnalysis(?string $imageAnalysis): static
    {
        $this->imageAnalysis = $imageAnalysis;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return Collection<int, Commentaire>
     */
    public function getCommentaires(): Collection
    {
        return $this->commentaires;
    }

    public function addCommentaire(Commentaire $commentaire): static
    {
        if (!$this->commentaires->contains($commentaire)) {
            $this->commentaires->add($commentaire);
            $commentaire->setPublication($this);
        }

        return $this;
    }

    public function removeCommentaire(Commentaire $commentaire): static
    {
        if ($this->commentaires->removeElement($commentaire)) {
            // set the owning side to null (unless already changed)
            if ($commentaire->getPublication() === $this) {
                $commentaire->setPublication(null);
            }
        }

        return $this;
    }

    public function getNbLikes(): ?int
    {
        return $this->nbLikes;
    }

    public function setNbLikes(?int $nbLikes): static
    {
        $this->nbLikes = $nbLikes;
        return $this;
    }

    public function getNbDislikes(): ?int
    {
        return $this->nbDislikes;
    }

    public function setNbDislikes(?int $nbDislikes): static
    {
        $this->nbDislikes = $nbDislikes;
        return $this;
    }

    /**
     * @return Collection<int, PublicationReaction>
     */
    public function getReactions(): Collection
    {
        return $this->reactions;
    }

    public function addReaction(PublicationReaction $reaction): static
    {
        if (!$this->reactions->contains($reaction)) {
            $this->reactions->add($reaction);
            $reaction->setPublication($this);
        }

        return $this;
    }

    public function removeReaction(PublicationReaction $reaction): static
    {
        if ($this->reactions->removeElement($reaction)) {
            if ($reaction->getPublication() === $this) {
                $reaction->setPublication(null);
            }
        }

        return $this;
    }
}
