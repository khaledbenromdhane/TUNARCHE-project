<?php

namespace App\Repository;

use App\Entity\Participation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Participation>
 *
 * @method Participation|null find($id, $lockMode = null, $lockVersion = null)
 * @method Participation|null findOneBy(array $criteria, array $orderBy = null)
 * @method Participation[]    findAll()
 * @method Participation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ParticipationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Participation::class);
    }

    /**
     * Save a Participation entity (insert or update).
     */
    public function save(Participation $participation, bool $flush = true): void
    {
        $this->getEntityManager()->persist($participation);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Remove a Participation entity.
     */
    public function remove(Participation $participation, bool $flush = true): void
    {
        $this->getEntityManager()->remove($participation);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Find all participations for a given event.
     */
    public function findByEvenement(int $evenementId): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.evenement = :evtId')
            ->setParameter('evtId', $evenementId)
            ->orderBy('p.dateParticipation', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Sum of all reserved places (nbr_participation) for a given event.
     * Only counts non-cancelled participations.
     */
    public function sumPlacesReservees(int $evenementId): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COALESCE(SUM(p.nbrParticipation), 0)')
            ->andWhere('p.evenement = :evtId')
            ->andWhere('p.statut != :annulee')
            ->setParameter('evtId', $evenementId)
            ->setParameter('annulee', 'Annulée')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Sum of reserved places for a given event, excluding a specific participation (for updates).
     */
    public function sumPlacesReserveesExcluding(int $evenementId, int $excludeParticipationId): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COALESCE(SUM(p.nbrParticipation), 0)')
            ->andWhere('p.evenement = :evtId')
            ->andWhere('p.statut != :annulee')
            ->andWhere('p.id != :excludeId')
            ->setParameter('evtId', $evenementId)
            ->setParameter('annulee', 'Annulée')
            ->setParameter('excludeId', $excludeParticipationId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Count all participations.
     */
    public function countAll(): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Count confirmed participations.
     */
    public function countConfirmed(): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->andWhere('p.statut = :statut')
            ->setParameter('statut', 'Confirmée')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Count pending participations.
     */
    public function countPending(): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->andWhere('p.statut = :statut')
            ->setParameter('statut', 'En attente')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Count cancelled participations.
     */
    public function countCancelled(): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->andWhere('p.statut = :statut')
            ->setParameter('statut', 'Annulée')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Sum all reserved places across all events.
     */
    public function sumAllPlaces(): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COALESCE(SUM(p.nbrParticipation), 0)')
            ->andWhere('p.statut != :annulee')
            ->setParameter('annulee', 'Annulée')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Find participations by user ID.
     */
    public function findByUser(int $userId): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.idUser = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('p.dateParticipation', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche + filtres + tri pour les pages index & admin.
     *
     * @param string $q         Mot-clé (cherche dans événement nom, statut, mode paiement)
     * @param string $statut    Filtre par statut (vide = tous)
     * @param string $paiement  Filtre par mode paiement (vide = tous, 'free' = sans paiement)
     * @param string $sort      Colonne de tri
     * @param string $order     Direction (ASC / DESC)
     */
    public function searchAndSort(string $q = '', string $statut = '', string $paiement = '', string $sort = 'dateParticipation', string $order = 'DESC'): array
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.evenement', 'e')
            ->addSelect('e');

        if ($q !== '') {
            $qb->andWhere('e.nom LIKE :kw OR p.statut LIKE :kw OR p.modePaiement LIKE :kw')
               ->setParameter('kw', '%' . $q . '%');
        }

        if ($statut !== '') {
            $qb->andWhere('p.statut = :statut')
               ->setParameter('statut', $statut);
        }

        if ($paiement !== '') {
            if ($paiement === 'free') {
                $qb->andWhere('p.modePaiement IS NULL');
            } else {
                $qb->andWhere('p.modePaiement = :mode')
                   ->setParameter('mode', $paiement);
            }
        }

        $allowed = [
            'dateParticipation' => 'p.dateParticipation',
            'nbrParticipation'  => 'p.nbrParticipation',
            'statut'            => 'p.statut',
            'modePaiement'      => 'p.modePaiement',
            'evenement'         => 'e.nom',
        ];
        $orderCol = $allowed[$sort] ?? 'p.dateParticipation';
        $order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';

        $qb->orderBy($orderCol, $order);

        return $qb->getQuery()->getResult();
    }
}
