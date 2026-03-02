<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function findArtistes(): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.role = :role')
            ->setParameter('role', 'artist')
            ->orderBy('u.nomuser', 'ASC')
            ->addOrderBy('u.prenomuser', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findStaticUser(int $id = 1): ?User
    {
        return $this->find($id);
    }
}
