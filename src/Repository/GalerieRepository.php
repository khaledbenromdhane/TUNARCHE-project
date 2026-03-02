<?php

namespace App\Repository;

use App\Entity\Galerie;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Galerie>
 */
class GalerieRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Galerie::class);
    }

    /**
     * @return Galerie[]
     */
    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('g')
            ->leftJoin('g.artistes', 'a')
            ->addSelect('a')
            ->orderBy('g.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche, tri et filtre pour la liste admin (AJAX).
     *
     * @param string      $search   Texte de recherche (nom, catégorie)
     * @param string|null $sortBy   Champ de tri: id, nom, categorie, nbOeuvresDispo, nbEmployes
     * @param string      $sortOrder ASC ou DESC
     * @param string|null $filterCategorie Filtre par catégorie
     * @return Galerie[]
     */
    public function searchFilterSort(string $search = '', ?string $sortBy = null, string $sortOrder = 'ASC', ?string $filterCategorie = null): array
    {
        $qb = $this->createQueryBuilder('g')
            ->leftJoin('g.artistes', 'a')
            ->addSelect('a');

        $search = trim($search);
        if ($search !== '') {
            $qb->andWhere('g.nom LIKE :search OR g.categorie LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        if ($filterCategorie !== null && $filterCategorie !== '') {
            $qb->andWhere('g.categorie = :cat')
                ->setParameter('cat', $filterCategorie);
        }

        $allowedSort = ['id' => 'g.idGalerie', 'nom' => 'g.nom', 'categorie' => 'g.categorie', 'nbOeuvresDispo' => 'g.nbOeuvresDispo', 'nbEmployes' => 'g.nbEmployes'];
        $sortField = isset($allowedSort[$sortBy ?? '']) ? $allowedSort[$sortBy] : 'g.nom';
        $direction = strtoupper($sortOrder) === 'DESC' ? 'DESC' : 'ASC';
        $qb->orderBy($sortField, $direction);

        return $qb->getQuery()->getResult();
    }

    /**
     * @return string[] Liste des catégories distinctes pour le filtre
     */
    public function findDistinctCategories(): array
    {
        $rows = $this->createQueryBuilder('g')
            ->select('g.categorie')
            ->distinct()
            ->orderBy('g.categorie', 'ASC')
            ->getQuery()
            ->getSingleColumnResult();
        return array_values(array_filter($rows));
    }
}
