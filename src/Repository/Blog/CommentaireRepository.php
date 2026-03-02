<?php

namespace App\Repository\Blog;

use App\Entity\Blog\Commentaire;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Commentaire>
 */
class CommentaireRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Commentaire::class);
    }

    /**
     * @return Commentaire[]
     */
    public function findFlagged(): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.estSignale = :flagged')
            ->setParameter('flagged', true)
            ->orderBy('c.dateCreation', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Commentaire[]
     */
    public function findRepliesByParentId(int $parentId): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.parentId = :parentId')
            ->setParameter('parentId', $parentId)
            ->orderBy('c.dateCreation', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
