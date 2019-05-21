<?php
namespace Comsa\BookingBundle\Event;

use Comsa\BookingBundle\Entity\Reservation;
use Symfony\Component\EventDispatcher\Event;

class ReservationCreated extends Event
{
    public const NAME = 'reservation.created';

    protected $reservation;

    public function __construct(Reservation $reservation)
    {
        $this->reservation = $reservation;
    }

    public function getReservation(): Reservation
    {
        return $this->reservation;
    }
}
