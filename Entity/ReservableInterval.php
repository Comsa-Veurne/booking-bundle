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
     * @Serializer\Groups({"interval", "reservable", "exception", "option"})
     */
    private $id;

    /**
     * @var null|float $price
     * @ORM\Column(type="decimal", precision=10, scale=2, nullable=true)
     * @Serializer\Groups({"reservable", "reservation"})
     */
    private $price;

    /**
     * @var null|float $pricePerPerson
     * @ORM\Column(type="decimal", precision=10, scale=2, nullable=true)
     * @Serializer\Groups({"reservable", "reservation"})
     */
    private $pricePerPerson;

    /**
     * @ORM\ManyToOne(targetEntity="Comsa\BookingBundle\Entity\Reservable", inversedBy="reservableIntervals")
     * @ORM\JoinColumn(nullable=false)
     */
    private $reservable;

    /**
     * @var Collection
     * @ORM\ManyToMany(targetEntity="Comsa\BookingBundle\Entity\Option", inversedBy="intervals")
     * @ORM\JoinTable(name="booking_reservables_intervals_options")
     */
    private $options;

    /**
     * @ORM\ManyToMany(targetEntity="Comsa\BookingBundle\Entity\ReservationException", mappedBy="intervals")
     */
    private $exceptions;

    /**
     * @ORM\Column(type="time")
     * @Serializer\Groups({"interval", "reservation", "reservable", "option"})
     */
    private $timeFrom;

    /**
     * @ORM\Column(type="time")
     * @Serializer\Groups({"interval", "reservation", "reservable", "option"})
     */
    private $timeTo;

    /**
     * @ORM\ManyToMany(targetEntity="Comsa\BookingBundle\Entity\Reservation", mappedBy="reservableIntervals")
     */
    private $reservations;

    public function __construct()
    {
        $this->reservations = new ArrayCollection();
        $this->exceptions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(?float $price): void
    {
        $this->price = $price;
    }

    /**
     * @return float|null
     */
    public function getPricePerPerson(): ?float
    {
        return $this->pricePerPerson;
    }

    /**
     * @param float|null $pricePerPerson
     */
    public function setPricePerPerson(?float $pricePerPerson): void
    {
        $this->pricePerPerson = $pricePerPerson;
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

    public function getTimeFrom(): ?\DateTime
    {
        return $this->timeFrom;
    }

    public function setTimeFrom(\DateTimeInterface $timeFrom): self
    {
        $this->timeFrom = $timeFrom;

        return $this;
    }

    public function getTimeTo(): ?\DateTime
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

    /**
     * @return Collection
     */
    public function getOptions(): Collection
    {
        return $this->options;
    }

    public function addOption(Option $option): self
    {
        if (!$this->options->contains($option)) {
            $this->options[] = $option;
            $option->addInterval($this);
        }

        return $this;
    }

    public function removeOption(Option $option): self
    {
        if ($this->options->contains($option)) {
            $this->options->removeElement($option);
            $option->removeInterval($this);
        }

        return $this;
    }

    public function __toString()
    {
        return $this->timeFrom->format('H:i') . ' - ' . $this->timeTo->format('H:i');
    }
}
