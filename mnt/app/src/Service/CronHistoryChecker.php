<?php

namespace App\Service;

use App\Entity\CronDate;
use Doctrine\ORM\EntityManagerInterface;
use Carbon\Carbon;

class CronHistoryChecker
{
    private EntityManagerInterface $entityManager;      

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function create(string $slug): void
    {
       $cronDate = new CronDate();
       $cronDate->setSlug($slug);
       $this->entityManager->persist($cronDate);
       $this->entityManager->flush();
    }

    public function getLastCronTime(string $slug): ?Carbon
    {
        $query = $this->entityManager->createQueryBuilder()
            ->select('cd')
            ->from(CronDate::class, 'cd')
            ->where('cd.slug = :slug')
            ->orderBy('cd.createdAt', 'DESC')
            ->setMaxResults(1)
            ->setParameter(
                'slug', $slug
            )
            ->getQuery();

            $result = $query->getOneOrNullResult();

            if ($result) {
                return new Carbon($result->getCreatedAt());
            }
             
            return null;
    }

}