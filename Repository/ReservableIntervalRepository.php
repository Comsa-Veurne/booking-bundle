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

    public function findMatches() {
        $intervals = $this->findAll();
        $matches = [];
        foreach ($intervals as $interval) {
            $identifier = $interval->getTimeFrom()->format('Hi') . $interval->getTimeTo()->format('Hi');
            if (!isset($matches[$identifier])) {
                $matches[$identifier]['intervals'] = [];
                $matches[$identifier]['capacity'] = 0;
            }
            $matches[$identifier]['intervals'][] = $interval;
            $matches[$identifier]['capacity'] += $interval->getReservable()->getCapacity();
        }
        return $matches;
    }
}
