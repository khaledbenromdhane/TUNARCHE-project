<?php

namespace App\Repository\Blog;

use App\Entity\Blog\Publication;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Publication>
 */
class PublicationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Publication::class);
    }

    /**
     * Save a publication entity
     */
    public function save(Publication $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Remove a publication entity
     */
    public function remove(Publication $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Find all publications ordered by date (newest first)
     */
    public function findAllOrderedByDate(): array
    {
        return $this->createQueryBuilder('p')
            ->orderBy('p.dateAct', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find publications by user ID
     */
    public function findByUserId(int $userId): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.idUser = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('p.dateAct', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Search publications by title or description
     */
    public function searchPublications(string $query): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.titre LIKE :query OR p.description LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('p.dateAct', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get recent publications (limit)
     */
    public function findRecent(int $limit = 10): array
    {
        return $this->createQueryBuilder('p')
            ->orderBy('p.dateAct', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Count total publications
     */
    public function countTotal(): int
    {
        return $this->createQueryBuilder('p')
            ->select('COUNT(p.idPublication)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Count publications by user
     */
    public function countByUser(int $userId): int
    {
        return $this->createQueryBuilder('p')
            ->select('COUNT(p.idPublication)')
            ->andWhere('p.idUser = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Finds a unique slug based on the provided slug.
     * If the slug already exists, it appends a number (e.g. -1, -2).
     */
    public function findUniqueSlug(string $slug, ?int $excludeId = null): string
    {
        $originalSlug = $slug;
        $count = 1;

        while ($this->slugExists($slug, $excludeId)) {
            $slug = $originalSlug . '-' . $count;
            $count++;
        }

        return $slug;
    }

    private function slugExists(string $slug, ?int $excludeId = null): bool
    {
        $qb = $this->createQueryBuilder('p')
            ->select('COUNT(p.idPublication)')
            ->where('p.slug = :slug')
            ->setParameter('slug', $slug);

        if ($excludeId) {
            $qb->andWhere('p.idPublication != :excludeId')
                ->setParameter('excludeId', $excludeId);
        }

        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }

    /**
     * Finds similar publications based on image analysis (style, color, brightness, saturation).
     */
    public function findSimilar(Publication $publication, int $limit = 3): array
    {
        $analysis = json_decode($publication->getImageAnalysis() ?? '{}', true);
        if (empty($analysis)) {
            // Fallback: finding recent publications from the same user or just recent ones
            return $this->createQueryBuilder('p')
                ->where('p.idPublication != :currentId')
                ->setParameter('currentId', $publication->getIdPublication())
                ->orderBy('p.idPublication', 'DESC')
                ->setMaxResults($limit)
                ->getQuery()
                ->getResult();
        }

        $styleTags = $analysis['style_tags'] ?? [];
        $visualElements = $analysis['visual_elements'] ?? [];
        $brightness = $analysis['brightness'] ?? '';
        $saturation = $analysis['saturation'] ?? '';

        $qb = $this->createQueryBuilder('p')
            ->where('p.idPublication != :currentId')
            ->setParameter('currentId', $publication->getIdPublication());

        // Basic filtering to get candidates
        $orX = $qb->expr()->orX();
        
        foreach ($styleTags as $i => $tag) {
            $orX->add($qb->expr()->like('p.imageAnalysis', ':style' . $i));
            $qb->setParameter('style' . $i, '%' . $tag . '%');
        }

        foreach ($visualElements as $i => $elem) {
            $orX->add($qb->expr()->like('p.imageAnalysis', ':elem' . $i));
            $qb->setParameter('elem' . $i, '%' . $elem . '%');
        }

        if ($brightness) {
            $orX->add($qb->expr()->like('p.imageAnalysis', ':brightness'));
            $qb->setParameter('brightness', '%' . $brightness . '%');
        }

        if ($saturation) {
            $orX->add($qb->expr()->like('p.imageAnalysis', ':saturation'));
            $qb->setParameter('saturation', '%' . $saturation . '%');
        }

        if ($orX->count() > 0) {
            $qb->andWhere($orX);
        }

        $candidates = $qb->getQuery()->getResult();

        // Rank candidates in PHP for better accuracy
        usort($candidates, function($a, $b) use ($styleTags, $visualElements, $brightness, $saturation) {
            $scoreA = $this->calculateSimilarityScore($a, $styleTags, $visualElements, $brightness, $saturation);
            $scoreB = $this->calculateSimilarityScore($b, $styleTags, $visualElements, $brightness, $saturation);
            return $scoreB <=> $scoreA;
        });

        return array_slice($candidates, 0, $limit);
    }

    private function calculateSimilarityScore(Publication $p, array $targetStyles, array $targetElements, string $targetBrightness, string $targetSaturation): int
    {
        $analysis = json_decode($p->getImageAnalysis() ?? '{}', true);
        if (empty($analysis)) return 0;

        $score = 0;
        
        // Exact style matches
        foreach ($analysis['style_tags'] ?? [] as $tag) {
            if (in_array($tag, $targetStyles)) $score += 10;
        }

        // Visual elements matches
        foreach ($analysis['visual_elements'] ?? [] as $elem) {
            if (in_array($elem, $targetElements)) $score += 5;
        }

        // Brightness & Saturation
        if (($analysis['brightness'] ?? '') === $targetBrightness) $score += 3;
        if (($analysis['saturation'] ?? '') === $targetSaturation) $score += 3;

        return $score;
    }
}
