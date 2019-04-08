<?php

namespace Comsa\BookingBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;

/**
 * @ORM\Entity(repositoryClass="Comsa\BookingBundle\Repository\ReservableIntervalRepository")
 * @ORM\Table("booking_reservables_intervals")
 */
class ReservableInterval
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @Serializer\Groups({"interval"})
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Comsa\BookingBundle\Entity\Reservable", inversedBy="reservableIntervals")
     * @ORM\JoinColumn(nullable=false)
     */
    private $reservable;

    /**
     * @ORM\Column(type="time")
     * @Serializer\Groups({"interval", "reservation"})
     */
    private $timeFrom;

    /**
     * @ORM\Column(type="time")
     * @Serializer\Groups({"interval", "reservation"})
     */
    private $timeTo;

    /**
     * @ORM\OneToMany(targetEntity="Comsa\BookingBundle\Entity\Reservation", mappedBy="reservableInterval")
     */
    private $reservations;

    public function __construct()
    {
        $this->reservations = new ArrayCollection();
    }

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

    public function getTimeFrom(): ?\DateTimeInterface
    {
        return $this->timeFrom;
    }

    public function setTimeFrom(\DateTimeInterface $timeFrom): self
    {
        $this->timeFrom = $timeFrom;

        return $this;
    }

    public function getTimeTo(): ?\DateTimeInterface
    {
        return $this->timeTo;
    }

    public function setTimeTo(\DateTimeInterface $timeTo): self
    {
        $this->timeTo = $timeTo;

        return $this;
    }

    /**
     * @return Collection|Reservation[]
     */
    public function getReservations(): Collection
    {
        return $this->reservations;
    }

    public function addReservation(Reservation $reservation): self
    {
        if (!$this->reservations->contains($reservation)) {
            $this->reservations[] = $reservation;
            $reservation->setReservableInterval($this);
        }

        return $this;
    }

    public function removeReservation(Reservation $reservation): self
    {
        if ($this->reservations->contains($reservation)) {
            $this->reservations->removeElement($reservation);
            // set the owning side to null (unless already changed)
            if ($reservation->getReservableInterval() === $this) {
                $reservation->setReservableInterval(null);
            }
        }

        return $this;
    }
}
