<?php
/**
 * Created by PhpStorm.
 * User: cirykpopeye
 * Date: 2019-04-08
 * Time: 09:46
 */

namespace Comsa\BookingBundle\Manager;


use Comsa\BookingBundle\Entity\Reservable;
use Comsa\BookingBundle\Entity\ReservableInterval;
use Comsa\BookingBundle\Entity\Reservation;
use Comsa\BookingBundle\Entity\ReservationException;
use Comsa\BookingBundle\Repository\ReservableIntervalRepository;
use Comsa\BookingBundle\Repository\ReservableRepository;
use Comsa\BookingBundle\Repository\ReservationExceptionRepository;
use Comsa\BookingBundle\Repository\ReservationRepository;

class BookingManager
{
    private $reservableRepository;
    private $reservationRepository;
    private $reservableIntervalRepository;
    private $exceptionRepository;

    const DAYS = [
        'Maandag',
        'Dinsdag',
        'Woensdag',
        'Donderdag',
        'Vrijdag',
        'Zaterdag',
        'Zondag'
    ];

    public function __construct(ReservableRepository $reservableRepository, ReservationRepository $reservationRepository, ReservableIntervalRepository $reservableIntervalRepository, ReservationExceptionRepository $exceptionRepository)
    {
        $this->reservableRepository = $reservableRepository;
        $this->reservationRepository = $reservationRepository;
        $this->reservableIntervalRepository = $reservableIntervalRepository;
        $this->exceptionRepository = $exceptionRepository;
    }

    public function getDisabledDatesForReservable(Reservable $reservable, array $dayRange): array
    {
        $disabledDates = [];

        foreach ($dayRange as $day) {
            if ($this->isDayDisabledForReservable($reservable, $day)) {
                $disabledDates[] = $day->format('Y-m-d');
            }
        }

        $disabledDates = array_unique($disabledDates);

        return $disabledDates;
    }

    public function isDayDisabledForReservable(Reservable $reservable, $date)
    {
        $intervals = $this->getIntervalsForReservableOnDate($reservable, $date);
        return count($intervals) > 0 ? false : true;
    }

    public function getIntervalsForReservableOnDate(Reservable $reservable, \DateTime $date)
    {
        $intervals = $reservable->getReservableIntervals()->filter(function(ReservableInterval $interval) use ($date, $reservable) {
            /**
             * These are only the reservations available for the date, reservable and activeTill, activeFrom
             * The only thing that needs to be validated is if it's the right day or date and that the intervals are available
             */
            $exceptions = $this->exceptionRepository->findAllForReservableAndDate($reservable, $date);

            $passedExceptions = true;
            /** @var ReservationException $exception */
            foreach ($exceptions as $exception) {
                if ($exception->getIntervals()->contains($interval)) {
                    $passedExceptions = false;
                    break;
                }

                if ($exception->getIntervals()->isEmpty()) {
                    $passedExceptions = false;
                    break;
                }
            }

            if (!$passedExceptions) {
                return $passedExceptions;
            }

            //-- Find if overlapping intervals ( f.e. a full day interval was reserved )
            $allReservations = $this->reservationRepository->findAllForCriteria(null, $date, $reservable);

            $overlap = false;
            /** @var Reservation $reservation */
            foreach ($allReservations as $reservation) {
                /** @var ReservableInterval $reservableInterval */
                foreach ($reservation->getReservableIntervals() as $reservableInterval) {

                    if (
                        ($interval->getTimeFrom() >= $reservableInterval->getTimeFrom() &&  $interval->getTimeFrom() <= $reservableInterval->getTimeTo()) ||
                        ($interval->getTimeFrom() <= $reservableInterval->getTimeFrom() && $interval->getTimeTo() >= $reservableInterval->getTimeFrom())
                    ) {
                        $overlap = true;
                        break 2;
                    }
                }
            }

            if ($overlap) {
                return false;
            }

            //-- Reservations on this timestamp?
            $reservations = $this->reservationRepository->findAllForCriteria($interval, $date, $reservable);
            return count($reservations) === 0;
        });

        return $intervals;
    }

    public function getDisabledDatesForAmountPersons(int $amountPersons, array $dayRange): array
    {
        //-- Get all possible reservables
        $reservables = $this->reservableRepository->findSuitableReservable($amountPersons);
        $disabledDates = [];
        $disabledDatesForReservables = [];
        foreach ($reservables as $reservable) {
            $disabledDatesForReservable = $this->getDisabledDatesForReservable($reservable, $dayRange);
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
