<?php

namespace App\Repository;

use App\Entity\Formation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class FormationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Formation::class);
    }

    /**
     * Logique de Recherche et Filtrage
     */
    public function findBySearchAndFilter(?string $search, ?string $type, string $sort, string $direction)
    {
        $qb = $this->createQueryBuilder('f');

        if ($search) {
            $qb->andWhere('f.nomForm LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        if ($type) {
            $qb->andWhere('f.type = :type')
               ->setParameter('type', $type);
        }

        // Sécurité du Tri (Whitelist)
        $allowedFields = ['f.nomForm', 'f.dateForm', 'f.type'];
        $sortField = in_array($sort, $allowedFields) ? $sort : 'f.id';
        $order = (strtoupper($direction) === 'DESC') ? 'DESC' : 'ASC';

        return $qb->orderBy($sortField, $order)
                  ->getQuery()
                  ->getResult();
    }
}