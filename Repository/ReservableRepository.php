<?php

namespace Comsa\BookingBundle\Repository;

use Comsa\BookingBundle\Entity\Reservable;
use Comsa\BookingBundle\Entity\ReservableInterval;
use Comsa\BookingBundle\Entity\Reservation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

/**
 * @method Reservable|null find($id, $lockMode = null, $lockVersion = null)
 * @method Reservable|null findOneBy(array $criteria, array $orderBy = null)
 * @method Reservable[]    findAll()
 * @method Reservable[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ReservableRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Reservable::class);
    }

    public function findSuitableReservable($amountPersons)
    {
        return $this->createQueryBuilder('i')
            ->where('i.capacity >= :amountPersons')
            ->setParameter('amountPersons', $amountPersons)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findSuitableReservables($amountPersons)
    {
        return $this->createQueryBuilder('i')
            ->where('i.capacity >= :amountPersons')
            ->setParameter('amountPersons', $amountPersons)
            ->getQuery()
            ->getResult();
    }

    public function findHighestCapacityForMatches($matches)
    {
        usort($matches, function($a, $b) {
            return $b['capacity'] - $a['capacity'];
        });
        return $matches[0]['capacity'];
    }

    public function findSufficientCapacityForMatches($matches, $amountPersons) {
        foreach ($matches as $key => $match) {
            if ($match['capacity'] < $amountPersons) {
                unset($matches[$key]);
            }
        }

        usort($matches, function($a, $b) {
            return $a['capacity'] - $b['capacity'];
        });
        return $matches[0];
    }

    public function findHighestCapacity()
    {
        $highestCapacityReservable = $this->createQueryBuilder('i')
            ->orderBy('i.capacity', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$highestCapacityReservable instanceof Reservable) {
            throw new ResourceNotFoundException();
        }

        return $highestCapacityReservable->getCapacity();
    }
}
