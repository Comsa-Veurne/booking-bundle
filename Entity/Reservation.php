<?php

namespace Comsa\BookingBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;

/**
 * @ORM\Entity(repositoryClass="Comsa\BookingBundle\Repository\ReservationRepository")
 * @ORM\Table("booking_reservations")
 */
class Reservation
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @Serializer\Groups({"reservation"})
     * @Serializer\Expose()
     */
    private $id;

    /**
     * @ORM\Column(type="integer")
     * @Serializer\Groups({"reservation"})
     * @Serializer\Expose()
     */
    private $amountPersons;

    /**
     * @ORM\ManyToOne(targetEntity="Comsa\BookingBundle\Entity\Address", inversedBy="reservations", cascade={"persist"})
     * @ORM\JoinColumn(nullable=false)
     * @Serializer\Groups({"reservation"})
     */
    private $address;

    /**
     * @ORM\OneToMany(targetEntity="Comsa\BookingBundle\Entity\ReservationOption", mappedBy="reservation", orphanRemoval=true, cascade={"persist"})
     * @Serializer\Groups({"reservation"})
     */
    private $reservationOptions;

    /**
     * @ORM\Column(type="date")
     * @Serializer\Groups({"reservation"})
     */
    private $date;

    /**
     * @ORM\ManyToMany(targetEntity="Comsa\BookingBundle\Entity\Reservable", inversedBy="reservations")
     * @ORM\JoinTable(name="booking_reservations_reservables")
     * @Serializer\Groups({"reservation"})
     */
    private $reservables;

    /**
     * @ORM\ManyToMany(targetEntity="Comsa\BookingBundle\Entity\ReservableInterval", inversedBy="reservations")
     * @ORM\JoinTable(name="booking_reservations_reservables_intervals")
     * @Serializer\Groups({"reservation"})
     */
    private $reservableIntervals;

    /**
     * @ORM\OneToOne(targetEntity="Comsa\BookingBundle\Entity\Payment", mappedBy="reservation")
     * @Serializer\Groups({"reservation"})
     */
    private $payment;

    public function __construct()
    {
        $this->reservationOptions = new ArrayCollection();
        $this->reservables = new ArrayCollection();
        $this->reservableIntervals = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAmountPersons(): ?int
    {
        return $this->amountPersons;
    }

    public function setAmountPersons(int $amountPersons): self
    {
        $this->amountPersons = $amountPersons;

        return $this;
    }

    public function getAddress(): ?Address
    {
        return $this->address;
    }

    public function setAddress(?Address $address): self
    {
        $this->address = $address;

        return $this;
    }

    /**
     * @return Collection|ReservationOption[]
     */
    public function getReservationOptions(): Collection
    {
        return $this->reservationOptions;
    }

    public function addReservationOption(ReservationOption $reservationOption): self
    {
        if (!$this->reservationOptions->contains($reservationOption)) {
            $this->reservationOptions[] = $reservationOption;
            $reservationOption->setReservation($this);
        }

        return $this;
    }

    public function removeReservationOption(ReservationOption $reservationOption): self
    {
        if ($this->reservationOptions->contains($reservationOption)) {
            $this->reservationOptions->removeElement($reservationOption);
            // set the owning side to null (unless already changed)
            if ($reservationOption->getReservation() === $this) {
                $reservationOption->setReservation(null);
            }
        }

        return $this;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): self
    {
        $this->date = $date;

        return $this;
    }

    public function getReservables(): Collection
    {
        return $this->reservables;
    }

    public function setReservables(Collection $reservables): self
    {
        $this->reservables = $reservables;

        return $this;
    }

    public function getReservableIntervals(): Collection
    {
        return $this->reservableIntervals;
    }

    public function setReservableIntervals(Collection $reservableIntervals): self
    {
        $this->reservableIntervals = $reservableIntervals;

        return $this;
    }

    /**
     * @return mixed
     * @Serializer\Groups({"reservation"})
     * @Serializer\VirtualProperty()
     */
    public function getStartDate()
    {
        $return = new \DateTime($this->getDate()->format('Y-m-d'));
        if ($this->getReservableIntervals()->first() instanceof ReservableInterval) {
            return new \DateTime($this->getDate()->format('Y-m-d') . ' ' . $this->getReservableIntervals()->first()->getTimeFrom()->format('H:i:s'));
        };
        return $return;
    }

    /**
     * @return \DateTime|false
     * @throws \Exception
     * @Serializer\Groups({"reservation"})
     * @Serializer\VirtualProperty()
     */
    public function getEndDate()
    {
        $return = new \DateTime($this->getDate()->format('Y-m-d'));
        if ($this->getReservableIntervals()->first() instanceof ReservableInterval) {
            return new \DateTime($this->getDate()->format('Y-m-d') . ' ' . $this->getReservableIntervals()->first()->getTimeTo()->format('H:i:s'));
        };
        return $return;
    }

    public function getRentalPrice()
    {
        $price = 0;
        /** @var ReservableInterval $interval */
        foreach ($this->getReservableIntervals() as $interval) {
            $price += $interval->getPrice();
        }

        //TODO: price per person
        return $price;
    }

    public function getOptionsPrice()
    {
        $price = 0;
        /** @var ReservationOption $option */
        foreach ($this->getReservationOptions() as $option) {
            $price += $option->getLinkedOption()->getPrice() * $option->getTimes();
        }
        return $price;
    }

    public function getTotal() {
        return $this->getRentalPrice() + $this->getOptionsPrice();
    }


    public function getPayment(): ?Payment
    {
        return $this->payment;
    }

    public function setPayment($payment): void
    {
        $this->payment = $payment;
    }
}
