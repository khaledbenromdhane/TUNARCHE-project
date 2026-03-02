<?php

namespace App\Entity\Blog;

use App\Entity\User;
use App\Repository\Blog\PublicationReactionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PublicationReactionRepository::class)]
#[ORM\Table(name: 'publication_reaction')]
#[ORM\UniqueConstraint(name: 'unique_user_publication_reaction', columns: ['id_user', 'id_publication'])]
class PublicationReaction
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_reaction', type: Types::INTEGER)]
    private ?int $idReaction = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'id_user', referencedColumnName: 'id_user', nullable: true)]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Publication::class, inversedBy: 'reactions')]
    #[ORM\JoinColumn(name: 'id_publication', referencedColumnName: 'id_publication', nullable: false)]
    private ?Publication $publication = null;

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

    public function getPublication(): ?Publication
    {
        return $this->publication;
    }

    public function setPublication(?Publication $publication): static
    {
        $this->publication = $publication;
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
