<?php

namespace Comsa\BookingBundle\Repository;

use Comsa\BookingBundle\Entity\ReservationOption;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method ReservationOption|null find($id, $lockMode = null, $lockVersion = null)
 * @method ReservationOption|null findOneBy(array $criteria, array $orderBy = null)
 * @method ReservationOption[]    findAll()
 * @method ReservationOption[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ReservationOptionRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, ReservationOption::class);
    }

    // /**
    //  * @return ReservationOption[] Returns an array of ReservationOption objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('r.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?ReservationOption
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
