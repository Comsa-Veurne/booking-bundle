<?php
/**
 * Created by PhpStorm.
 * User: cirykpopeye
 * Date: 2019-04-08
 * Time: 09:46
 */

namespace Comsa\BookingBundle\Manager;


use Comsa\BookingBundle\Entity\Reservable;
use Comsa\BookingBundle\Entity\Reservation;
use Comsa\BookingBundle\Repository\ReservableIntervalRepository;
use Comsa\BookingBundle\Repository\ReservableRepository;
use Comsa\BookingBundle\Repository\ReservationRepository;

class BookingManager
{
    private $reservableRepository;
    private $reservationRepository;
    private $reservableIntervalRepository;

    public function __construct(ReservableRepository $reservableRepository, ReservationRepository $reservationRepository, ReservableIntervalRepository $reservableIntervalRepository)
    {
        $this->reservableRepository = $reservableRepository;
        $this->reservationRepository = $reservationRepository;
        $this->reservableIntervalRepository = $reservableIntervalRepository;
    }

    public function getDisabledDatesForReservable(Reservable $reservable): array
    {
        $reservations = $this->reservationRepository->findAllUpcomingReservationsByReservable($reservable);
        $disabledDates = [];

        /** @var Reservation $reservation */
        foreach ($reservations as $reservation) {
            if ($this->isDayDisabledForReservable($reservable, $reservation->getDate())) {
                if (!in_array($reservation->getDate(), $disabledDates)) {
                    $disabledDates[] = $reservation->getDate()->format('Y-m-d');
                }
            }
        }

        $disabledDates = array_unique($disabledDates);

        return $disabledDates;
    }

    public function isDayDisabledForReservable(Reservable $reservable, $date)
    {
        return count($this->getIntervalsForReservableOnDate($reservable, $date)) > 0 ? false : true;
    }

    public function getIntervalsForReservableOnDate(Reservable $reservable, \DateTime $date)
    {
        $reservations = $this->reservationRepository->findBy([
            'date' => $date,
            'reservable' => $reservable
        ]);

        if (count($reservations)) {
            $intervals = $reservable->getReservableIntervals();
            foreach ($intervals as $key => $interval) {
                if (count($this->reservationRepository->findBy([
                    'reservableInterval' => $interval,
                    'date' => $date,
                    'reservable' => $reservable
                ])) > 0) {
                    unset($intervals[$key]);
                }
            }
        } else {
            $intervals = $reservable->getReservableIntervals();
        }

        return $intervals;
    }

    public function getDisabledDatesForAmountPersons(int $amountPersons): array
    {
        //-- Get all possible reservables
        $reservables = $this->reservableRepository->findSuitableReservable($amountPersons);
        $disabledDates = [];
        $disabledDatesForReservables = [];
        foreach ($reservables as $reservable) {
            $disabledDatesForReservable = $this->getDisabledDatesForReservable($reservable);
            $disabledDatesForReservables[] = $disabledDatesForReservable;
            $disabledDates = array_merge($disabledDates, $disabledDatesForReservable);
        }

        $disabledDates = array_unique($disabledDates);

        foreach ($disabledDatesForReservables as $disabledDatesForReservable) {
            foreach ($disabledDates as $key => $disabledDate) {
                if (!in_array($disabledDate, $disabledDatesForReservable)) {
                    unset($disabledDates[$key]);
                }
            }
        }

        return $disabledDates;
    }
}
