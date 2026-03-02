<?php

namespace App\Entity\Blog;

use App\Entity\User;
use App\Repository\Blog\CommentaireReactionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CommentaireReactionRepository::class)]
#[ORM\Table(name: 'commentaire_reaction')]
#[ORM\UniqueConstraint(name: 'unique_user_commentaire_reaction', columns: ['id_user', 'id_commentaire'])]
class CommentaireReaction
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_reaction', type: Types::INTEGER)]
    private ?int $idReaction = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'id_user', referencedColumnName: 'id_user', nullable: true)]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Commentaire::class, inversedBy: 'reactions')]
    #[ORM\JoinColumn(name: 'id_commentaire', referencedColumnName: 'id_commentaire', nullable: false)]
    private ?Commentaire $commentaire = null;

    #[ORM\Column(name: 'is_like', type: Types::BOOLEAN)]
    private ?bool $isLike = null;

    public function getIdReaction(): ?int
    {
        return $this->idReaction;
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

    public function getCommentaire(): ?Commentaire
    {
        return $this->commentaire;
    }

    public function setCommentaire(?Commentaire $commentaire): static
    {
        $this->commentaire = $commentaire;
        return $this;
    }

    public function isLike(): ?bool
    {
        return $this->isLike;
    }

    public function setIsLike(bool $isLike): static
    {
        $this->isLike = $isLike;
        return $this;
    }
}
