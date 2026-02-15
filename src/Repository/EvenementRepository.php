<?php

namespace App\Repository;

use App\Entity\Evenement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Evenement>
 *
 * @method Evenement|null find($id, $lockMode = null, $lockVersion = null)
 * @method Evenement|null findOneBy(array $criteria, array $orderBy = null)
 * @method Evenement[]    findAll()
 * @method Evenement[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EvenementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Evenement::class);
    }

    /**
     * Save an Evenement entity (insert or update).
     */
    public function save(Evenement $evenement, bool $flush = true): void
    {
        $this->getEntityManager()->persist($evenement);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Remove an Evenement entity.
     */
    public function remove(Evenement $evenement, bool $flush = true): void
    {
        $this->getEntityManager()->remove($evenement);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Find events by type.
     */
    public function findByType(string $type): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.typeEvenement = :type')
            ->setParameter('type', $type)
            ->orderBy('e.date', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find upcoming events (date >= today).
     */
    public function findUpcoming(): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.date >= :today')
            ->setParameter('today', new \DateTime('today'))
            ->orderBy('e.date', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Count paid events.
     */
    public function countPaid(): int
    {
        return (int) $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->andWhere('e.paiement = :paid')
            ->setParameter('paid', true)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Sum of all participants.
     */
    public function sumParticipants(): int
    {
        return (int) $this->createQueryBuilder('e')
            ->select('COALESCE(SUM(e.nbrParticipant), 0)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Search events by keyword (nom or lieu).
     */
    public function search(string $keyword): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.nom LIKE :kw OR e.lieu LIKE :kw OR e.description LIKE :kw')
            ->setParameter('kw', '%' . $keyword . '%')
            ->orderBy('e.date', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche + filtres + tri pour les pages index & admin.
     *
     * @param string $q         Mot-clé (cherche dans nom, lieu, description, type)
     * @param string $type      Filtre par typeEvenement (vide = tous)
     * @param string $paiement  Filtre par paiement ('1' = payant, '0' = gratuit, '' = tous)
     * @param string $sort      Colonne de tri
     * @param string $order     Direction (ASC / DESC)
     */
    public function searchAndSort(string $q = '', string $type = '', string $paiement = '', string $sort = 'date', string $order = 'DESC'): array
    {
        $qb = $this->createQueryBuilder('e');

        if ($q !== '') {
            $qb->andWhere('e.nom LIKE :kw OR e.lieu LIKE :kw OR e.description LIKE :kw OR e.typeEvenement LIKE :kw')
               ->setParameter('kw', '%' . $q . '%');
        }

        if ($type !== '') {
            $qb->andWhere('e.typeEvenement = :type')
               ->setParameter('type', $type);
        }

        if ($paiement !== '') { 
            $qb->andWhere('e.paiement = :paid')
               ->setParameter('paid', $paiement === '1');
        }

        $allowed = ['nom', 'date', 'lieu', 'typeEvenement', 'nbrParticipant', 'paiement', 'heure'];
        if (!in_array($sort, $allowed, true)) {
            $sort = 'date';
        }
        $order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';

        $qb->orderBy('e.' . $sort, $order);

        return $qb->getQuery()->getResult();
    }
}
