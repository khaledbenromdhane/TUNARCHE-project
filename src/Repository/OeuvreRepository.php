<?php

namespace App\Repository;

use App\Entity\Oeuvre;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Oeuvre>
 */
class OeuvreRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Oeuvre::class);
    }

    /**
     * @return Oeuvre[]
     */
    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('o')
            ->leftJoin('o.artiste', 'a')
            ->addSelect('a')
            ->leftJoin('o.galerie', 'g')
            ->addSelect('g')
            ->orderBy('o.titre', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Liste des œuvres pour le front avec filtre disponible/vendue.
     * @param string|null $filtre 'disponible' | 'vendue' | null (toutes)
     * @return Oeuvre[]
     */
    public function findForFront(?string $filtre = null): array
    {
        $qb = $this->createQueryBuilder('o')
            ->leftJoin('o.artiste', 'a')
            ->addSelect('a')
            ->leftJoin('o.galerie', 'g')
            ->addSelect('g')
            ->orderBy('o.titre', 'ASC');

        if ($filtre === 'disponible') {
            $qb->andWhere('o.statut = :statut')->setParameter('statut', 'disponible');
        } elseif ($filtre === 'vendue') {
            $qb->andWhere('o.statut = :statut')->setParameter('statut', 'vendue');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Recherche, tri et filtre pour la liste admin (AJAX).
     *
     * @param string   $search   Texte de recherche (titre, description)
     * @param string|null $sortBy Champ de tri: id, titre, prix, annee, etat, galerie
     * @param string   $sortOrder ASC ou DESC
     * @param int|null $filterGalerie Id galerie pour filtre
     * @param string|null $filterEtat  Filtre par état (neuve, défectueuse)
     * @return Oeuvre[]
     */
    public function searchFilterSort(string $search = '', ?string $sortBy = null, string $sortOrder = 'ASC', ?int $filterGalerie = null, ?string $filterEtat = null): array
    {
        $qb = $this->createQueryBuilder('o')
            ->leftJoin('o.artiste', 'a')
            ->addSelect('a')
            ->leftJoin('o.galerie', 'g')
            ->addSelect('g');

        $search = trim($search);
        if ($search !== '') {
            $qb->andWhere('o.titre LIKE :search OR o.description LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        if ($filterGalerie !== null && $filterGalerie > 0) {
            $qb->andWhere('g.idGalerie = :galerieId')
                ->setParameter('galerieId', $filterGalerie);
        }

        if ($filterEtat !== null && $filterEtat !== '') {
            $qb->andWhere('o.etat = :etat')
                ->setParameter('etat', $filterEtat);
        }

        $allowedSort = [
            'id' => 'o.id',
            'titre' => 'o.titre',
            'prix' => 'o.prix',
            'annee' => 'o.anneeRealisation',
            'etat' => 'o.etat',
            'galerie' => 'g.nom',
        ];
        $sortField = isset($allowedSort[$sortBy ?? '']) ? $allowedSort[$sortBy] : 'o.titre';
        $direction = strtoupper($sortOrder) === 'DESC' ? 'DESC' : 'ASC';
        $qb->orderBy($sortField, $direction);

        return $qb->getQuery()->getResult();
    }

    /**
     * Œuvres vendues (pour export Excel admin).
     * @return Oeuvre[]
     */
    public function findVendues(): array
    {
        return $this->createQueryBuilder('o')
            ->leftJoin('o.artiste', 'a')
            ->addSelect('a')
            ->leftJoin('o.galerie', 'g')
            ->addSelect('g')
            ->where('o.statut = :statut')
            ->setParameter('statut', 'vendue')
            ->orderBy('o.titre', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Statistiques des ventes pour les graphiques admin (Chart.js).
     * @return array{ nb_disponibles: int, nb_vendues: int, total_revenus: float, prix_moyen: float, ventes_par_mois: list<array{mois: string, mois_label: string, count: int, revenus: float}>, revenus_par_galerie: list<array{galerie: string, count: int, revenus: float}> }
     */
    public function getStatsVentes(): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $qb = $this->createQueryBuilder('o');
        $qb->select('COUNT(o.id)')->where('o.statut = :statut')->setParameter('statut', 'vendue');
        $nbVendues = (int) $qb->getQuery()->getSingleScalarResult();
        $qb2 = $this->createQueryBuilder('o');
        $qb2->select('COALESCE(SUM(o.prix), 0)')->where('o.statut = :statut')->setParameter('statut', 'vendue');
        $totalRevenus = (float) $qb2->getQuery()->getSingleScalarResult();
        $prixMoyen = $nbVendues > 0 ? $totalRevenus / $nbVendues : 0.0;

        $qb2 = $this->createQueryBuilder('o');
        $qb2->select('COUNT(o.id)');
        $qb2->where('o.statut = :s')->setParameter('s', 'disponible');
        $nbDisponibles = (int) $qb2->getQuery()->getSingleScalarResult();

        $ventesParMois = [];
        $sqlMois = "SELECT DATE_FORMAT(date_vente, '%Y-%m') AS mois, COUNT(*) AS cnt, COALESCE(SUM(prix), 0) AS revenus FROM oeuvre WHERE statut = 'vendue' AND date_vente IS NOT NULL GROUP BY mois ORDER BY mois";
        try {
            $stmt = $conn->executeQuery($sqlMois);
            $rows = $stmt->fetchAllAssociative();
            $moisLabels = ['01' => 'Jan', '02' => 'Fév', '03' => 'Mar', '04' => 'Avr', '05' => 'Mai', '06' => 'Juin', '07' => 'Juil', '08' => 'Août', '09' => 'Sep', '10' => 'Oct', '11' => 'Nov', '12' => 'Déc'];
            foreach ($rows as $r) {
                $mois = $r['mois'];
                $parts = explode('-', $mois);
                $label = ($moisLabels[$parts[1] ?? ''] ?? $parts[1] ?? '') . ' ' . ($parts[0] ?? '');
                $ventesParMois[] = [
                    'mois' => $mois,
                    'mois_label' => $label,
                    'count' => (int) $r['cnt'],
                    'revenus' => (float) $r['revenus'],
                ];
            }
        } catch (\Throwable $e) {
            // table might not have date_vente yet
        }

        $revenusParGalerie = [];
        $sqlGal = "SELECT COALESCE(g.nom, 'Sans galerie') AS galerie, COUNT(o.id) AS cnt, COALESCE(SUM(o.prix), 0) AS revenus FROM oeuvre o LEFT JOIN galerie g ON o.id_galerie = g.id_galerie WHERE o.statut = 'vendue' GROUP BY o.id_galerie, g.nom ORDER BY revenus DESC";
        try {
            $stmt = $conn->executeQuery($sqlGal);
            foreach ($stmt->fetchAllAssociative() as $r) {
                $revenusParGalerie[] = [
                    'galerie' => $r['galerie'],
                    'count' => (int) $r['cnt'],
                    'revenus' => (float) $r['revenus'],
                ];
            }
        } catch (\Throwable $e) {
        }

        return [
            'nb_disponibles' => $nbDisponibles,
            'nb_vendues' => $nbVendues,
            'total_revenus' => $totalRevenus,
            'prix_moyen' => $prixMoyen,
            'ventes_par_mois' => $ventesParMois,
            'revenus_par_galerie' => $revenusParGalerie,
        ];
    }
}
