<?php

namespace Comsa\BookingBundle\Repository;

use Comsa\BookingBundle\Entity\Reservable;
use Comsa\BookingBundle\Entity\ReservableInterval;
use Comsa\BookingBundle\Entity\Reservation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method ReservableInterval|null find($id, $lockMode = null, $lockVersion = null)
 * @method ReservableInterval|null findOneBy(array $criteria, array $orderBy = null)
 * @method ReservableInterval[]    findAll()
 * @method ReservableInterval[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ReservableIntervalRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, ReservableInterval::class);
    }

    public function findIntervalsForReservableOnDate(?Reservable $reservable, $date, $amountPersons = null)
    {
        if (!$reservable) {
            return $this->createQueryBuilder('i')
                ->innerJoin('i.reservable', 'rv')
                ->where('rv.capacity >= :amountPersons')
                ->setParameter('amountPersons', $amountPersons)
                ->getQuery()
                ->getResult();
        }
        //-- For now simply return the intervals, later expand per date filter
        return $reservable->getReservableIntervals();
    }
    // /**
    //  * @return ReservableInterval[] Returns an array of ReservableInterval objects
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
    public function findOneBySomeField($value): ?ReservableInterval
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
