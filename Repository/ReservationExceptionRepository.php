<?php

namespace Comsa\BookingBundle\Repository;

use Comsa\BookingBundle\Entity\Reservable;
use Comsa\BookingBundle\Entity\Reservation;
use Comsa\BookingBundle\Entity\ReservationException;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

class ReservationExceptionRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, ReservationException::class);
    }

    public function findAllUpcomingExceptionsByReservable(Reservable $reservable) {
        return $this->createQueryBuilder('e')
            ->where('e.reservable = :reservable OR e.reservable IS NULL')
            ->andWhere('e.date >= :now OR e.date IS NULL')
            ->andWhere('e.activeTill >= :now OR e.activeTill IS NULL')
            ->setParameter('reservable', $reservable)
            ->setParameter('now', (new \DateTime())->format('Y-m-d'))
            ->getQuery()
            ->getResult();
    }

    public function findAllForReservableAndDate(Reservable $reservable, \DateTime $date)
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.day = :day OR e.date = :date OR (e.date IS NULL AND e.day IS NULL)')
            ->andWhere('e.reservable = :reservable OR e.reservable IS NULL')
            ->andWhere('e.activeFrom <= :now OR e.activeFrom IS NULL')
            ->andWhere('e.activeTill >= :now OR e.activeTill IS NULL')
            ->setParameters([
                'day' => $date->format('N'),
                'date' => $date->format('Y-m-d'),
                'reservable' => $reservable,
                'now' => $date->format('Y-m-d')
            ])
            ->getQuery()
            ->getResult();
    }
}
