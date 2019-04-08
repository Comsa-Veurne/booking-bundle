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
     * @ORM\ManyToOne(targetEntity="Comsa\BookingBundle\Entity\Reservable", inversedBy="reservations")
     * @ORM\JoinColumn(nullable=false)
     * @Serializer\Groups({"reservation"})
     */
    private $reservable;

    /**
     * @ORM\ManyToOne(targetEntity="Comsa\BookingBundle\Entity\ReservableInterval", inversedBy="reservations")
     * @ORM\JoinColumn(nullable=false)
     * @Serializer\Groups({"reservation"})
     */
    private $reservableInterval;

    public function __construct()
    {
        $this->reservationOptions = new ArrayCollection();
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

    public function getReservable(): ?Reservable
    {
        return $this->reservable;
    }

    public function setReservable(?Reservable $reservable): self
    {
        $this->reservable = $reservable;

        return $this;
    }

    public function getReservableInterval(): ?ReservableInterval
    {
        return $this->reservableInterval;
    }

    public function setReservableInterval(?ReservableInterval $reservableInterval): self
    {
        $this->reservableInterval = $reservableInterval;

        return $this;
    }

    /**
     * @return mixed
     * @Serializer\Groups({"reservation"})
     * @Serializer\VirtualProperty()
     */
    public function getStartDate()
    {
        return new \DateTime($this->getDate()->format('Y-m-d') . ' ' . $this->getReservableInterval()->getTimeFrom()->format('H:i:s'));
    }

    /**
     * @return \DateTime|false
     * @throws \Exception
     * @Serializer\Groups({"reservation"})
     * @Serializer\VirtualProperty()
     */
    public function getEndDate()
    {
        return new \DateTime($this->getDate()->format('Y-m-d') . ' ' . $this->getReservableInterval()->getTimeTo()->format('H:i:s'));
    }
}
