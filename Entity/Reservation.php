<?php

namespace Comsa\BookingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="Comsa\BookingBundle\Repository\ReservationRepository")
 */
class Reservation
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Comsa\BookingBundle\Entity\Reservable", inversedBy="reservations")
     * @ORM\JoinColumn(nullable=false)
     */
    private $reservable;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReservable(): ?Reservable
    {
        return $this->reservable;
    }

    public function setReservable(?Reservable $reservable): self
    {
        $this->reservable = $reservable;

        return $this;
    }
}
