<?php

namespace Comsa\BookingBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;

/**
 * @ORM\Entity(repositoryClass="Comsa\BookingBundle\Repository\ReservableRepository")
 * @ORM\Table("booking_reservables")
 */
class Reservable
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @Serializer\Groups({"reservable"})
     */
    private $id;

    /**
     * @ORM\Column(type="integer")
     * @Serializer\Groups({"reservable"})
     */
    private $capacity;

    /**
     * @ORM\Column(type="string", length=255)
     * @Serializer\Groups({"reservable", "reservation"})
     */
    private $title;

    /**
     * @ORM\OneToMany(targetEntity="Comsa\BookingBundle\Entity\ReservableInterval", mappedBy="reservable", orphanRemoval=true, cascade={"persist"})
     * @Serializer\Groups({"reservable"})
     */
    private $reservableIntervals;

    /**
     * @ORM\ManyToMany(targetEntity="Comsa\BookingBundle\Entity\Reservation", mappedBy="reservables")
     */
    private $reservations;

    public function __construct()
    {
        $this->reservableIntervals = new ArrayCollection();
        $this->reservations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCapacity(): ?int
    {
        return $this->capacity;
    }

    public function setCapacity(int $capacity): self
    {
        $this->capacity = $capacity;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function setReservableIntervals($reservableIntervals): void
    {
        $this->reservableIntervals = $reservableIntervals;
    }

    /**
     * @return Collection|ReservableInterval[]
     */
    public function getReservableIntervals(): Collection
    {
        return $this->reservableIntervals;
    }

    public function addReservableInterval(ReservableInterval $reservableInterval): self
    {
        if (!$this->reservableIntervals->contains($reservableInterval)) {
            $this->reservableIntervals[] = $reservableInterval;
            $reservableInterval->setReservable($this);
        }

        return $this;
    }

    public function removeReservableInterval(ReservableInterval $reservableInterval): self
    {
        if ($this->reservableIntervals->contains($reservableInterval)) {
            $this->reservableIntervals->removeElement($reservableInterval);
            // set the owning side to null (unless already changed)
            if ($reservableInterval->getReservable() === $this) {
                $reservableInterval->setReservable(null);
            }
        }

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
            $reservation->setReservable($this);
        }

        return $this;
    }

    public function removeReservation(Reservation $reservation): self
    {
        if ($this->reservations->contains($reservation)) {
            $this->reservations->removeElement($reservation);
            // set the owning side to null (unless already changed)
            if ($reservation->getReservable() === $this) {
                $reservation->setReservable(null);
            }
        }

        return $this;
    }
}
