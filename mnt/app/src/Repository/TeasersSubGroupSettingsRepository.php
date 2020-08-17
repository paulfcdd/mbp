<?php

namespace App\Repository;

use App\Entity\Country;
use App\Entity\TeasersSubGroup;
use App\Entity\TeasersSubGroupSettings;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method TeasersSubGroupSettings|null find($id, $lockMode = null, $lockVersion = null)
 * @method TeasersSubGroupSettings|null findOneBy(array $criteria, array $orderBy = null)
 * @method TeasersSubGroupSettings[]    findAll()
 * @method TeasersSubGroupSettings[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TeasersSubGroupSettingsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TeasersSubGroupSettings::class);
    }

    public function getDefaultSubGroupSettings(TeasersSubGroup $subGroup)
    {
        $query = $this->createQueryBuilder('tsgSettings')
            ->where('tsgSettings.teasersSubGroup = :subGroup')
            ->andWhere('tsgSettings.geoCode IS NULL')
            ->setParameters([
                'subGroup' => $subGroup,
            ])
            ->getQuery();

        return $query->getOneOrNullResult();
    }

    public function getCountrySubGroupSettings(TeasersSubGroup $subGroup, Country $country)
    {
        $query = $this->createQueryBuilder('tsgSettings')
            ->where('tsgSettings.teasersSubGroup = :subGroup')
            ->andWhere('tsgSettings.geoCode = :country')
            ->setParameters([
                'subGroup' => $subGroup,
                'country' => $country,
            ])
            ->getQuery();

        return $query->getOneOrNullResult();
    }

    public function getByUnDeletedSubGroup()
    {
        $query = $this->createQueryBuilder('tsgSettings')
            ->leftJoin('tsgSettings.teasersSubGroup', 'tsg')
            ->where('tsg.is_deleted = :isDeleted')
            ->setParameter('isDeleted', false)
            ->getQuery();

        return $query->getResult();
    }
}
