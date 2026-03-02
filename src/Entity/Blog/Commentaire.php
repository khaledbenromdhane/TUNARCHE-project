<?php

namespace App\Entity\Blog;

use App\Entity\User;
use App\Repository\Blog\CommentaireRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CommentaireRepository::class)]
#[ORM\Table(name: 'commentaire')]
class Commentaire
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_commentaire', type: Types::INTEGER)]
    private ?int $idCommentaire = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'id_user', referencedColumnName: 'id_user', nullable: true)]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Publication::class, inversedBy: 'commentaires')]
    #[ORM\JoinColumn(name: 'id_publication', referencedColumnName: 'id_publication', nullable: false)]
    private ?Publication $publication = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $content = null;

    #[ORM\Column(name: 'date_creation', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateCreation = null;

    #[ORM\Column(length: 255)]
    private ?string $status = null;

    #[ORM\Column(name: 'nb_likes', type: Types::INTEGER, nullable: true)]
    private ?int $nbLikes = null;

    #[ORM\Column(name: 'nb_dislikes', type: Types::INTEGER, nullable: true)]
    private ?int $nbDislikes = null;

    #[ORM\OneToMany(targetEntity: CommentaireReaction::class, mappedBy: 'commentaire', cascade: ['remove'])]
    private Collection $reactions;

    #[ORM\Column(name: 'parent_id', type: Types::INTEGER, nullable: false, options: ['default' => 0])]
    private int $parentId = 0;

    #[ORM\Column(name: 'est_signale', type: Types::BOOLEAN)]
    private ?bool $estSignale = false;

    #[ORM\Column(name: 'raison_signalement', length: 255, nullable: true)]
    private ?string $raisonSignalement = null;

    public function __construct()
    {
        $this->reactions = new ArrayCollection();
        $this->nbLikes = 0;
        $this->nbDislikes = 0;
        $this->estSignale = false;
        $this->parentId = 0;
        $this->dateCreation = new \DateTime();
    }

    public function getIdCommentaire(): ?int
    {
        return $this->idCommentaire;
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

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;

        return $this;
    }

    public function getDateCreation(): ?\DateTimeInterface
    {
        return $this->dateCreation;
    }

    public function setDateCreation(?\DateTimeInterface $dateCreation): static
    {
        $this->dateCreation = $dateCreation;

        return $this;
    }

    public function getPublication(): ?Publication
    {
        return $this->publication;
    }

    public function setPublication(?Publication $publication): static
    {
        $this->publication = $publication;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

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

    public function getParentId(): int
    {
        return $this->parentId;
    }

    public function setParentId(int $parentId): static
    {
        $this->parentId = $parentId;

        return $this;
    }

    public function isEstSignale(): ?bool
    {
        return $this->estSignale;
    }

    public function setEstSignale(bool $estSignale): static
    {
        $this->estSignale = $estSignale;

        return $this;
    }

    public function getRaisonSignalement(): ?string
    {
        return $this->raisonSignalement;
    }

    public function setRaisonSignalement(?string $raisonSignalement): static
    {
        $this->raisonSignalement = $raisonSignalement;

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
     * @return Collection<int, CommentaireReaction>
     */
    public function getReactions(): Collection
    {
        return $this->reactions;
    }

    public function addReaction(CommentaireReaction $reaction): static
    {
        if (!$this->reactions->contains($reaction)) {
            $this->reactions->add($reaction);
            $reaction->setCommentaire($this);
        }

        return $this;
    }

    public function removeReaction(CommentaireReaction $reaction): static
    {
        if ($this->reactions->removeElement($reaction)) {
            if ($reaction->getCommentaire() === $this) {
                $reaction->setCommentaire(null);
            }
        }

        return $this;
    }
}
