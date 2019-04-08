<?php

namespace Comsa\BookingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;

/**
 * @ORM\Entity(repositoryClass="Comsa\BookingBundle\Repository\ReservationOptionRepository")
 * @ORM\Table("booking_reservations_options")
 */
class ReservationOption
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Comsa\BookingBundle\Entity\Reservation", inversedBy="reservationOptions")
     * @ORM\JoinColumn(nullable=false)
     */
    private $reservation;

    /**
     * @ORM\ManyToOne(targetEntity="Comsa\BookingBundle\Entity\Option")
     * @ORM\JoinColumn(nullable=false)
     * @Serializer\Groups({"reservation"})
     */
    private $linkedOption;

    /**
     * @ORM\Column(type="integer")
     * @Serializer\Groups({"reservation"})
     */
    private $times;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReservation(): ?Reservation
    {
        return $this->reservation;
    }

    public function setReservation(?Reservation $reservation): self
    {
        $this->reservation = $reservation;

        return $this;
    }

    public function getLinkedOption(): ?Option
    {
        return $this->linkedOption;
    }

    public function setLinkedOption(?Option $linkedOption): self
    {
        $this->linkedOption = $linkedOption;

        return $this;
    }

    public function getTimes(): ?int
    {
        return $this->times;
    }

    public function setTimes(int $times): self
    {
        $this->times = $times;

        return $this;
    }

    public function isValid()
    {
        return $this->times > 0;
    }
}
