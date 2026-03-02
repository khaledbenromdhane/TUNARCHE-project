<?php

namespace App\Repository;

use App\Entity\Evaluation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Evaluation>
 */
class EvaluationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Evaluation::class);
    }

    //    /**
    //     * @return Evaluation[] Returns an array of Evaluation objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('e')
    //            ->andWhere('e.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('e.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Evaluation
    //    {
    //        return $this->createQueryBuilder('e')
    //            ->andWhere('e.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }


    public function findBySearchAndFilter(?string $search, ?int $note, string $sort = 'e.id', string $direction = 'DESC')
{
    $qb = $this->createQueryBuilder('e')
        ->leftJoin('e.formation', 'f') // Pour chercher par nom de formation
        ->addSelect('f');

    if ($search) {
        $qb->andWhere('e.titre LIKE :s OR f.nomForm LIKE :s')
           ->setParameter('s', '%' . $search . '%');
    }

    if ($note !== null) {
        $qb->andWhere('e.note = :note')
           ->setParameter('note', $note);
    }

    $direction = (strtoupper($direction) === 'DESC') ? 'DESC' : 'ASC';
    return $qb->orderBy($sort, $direction)->getQuery()->getResult();
}



}
