<?php
/**
 * Created by PhpStorm.
 * User: cirykpopeye
 * Date: 2019-05-06
 * Time: 13:59
 */

namespace Comsa\BookingBundle\Entity;


use Comsa\BookingBundle\Manager\BookingManager;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;

/**
 * @ORM\Entity(repositoryClass="Comsa\BookingBundle\Repository\ReservationExceptionRepository")
 * @ORM\Table(name="booking_reservations_exceptions")
 */
class ReservationException
{
    /**
     * @var int $id
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     * @Serializer\Groups({"exceptions", "exception"})
     */
    private $id;

    /**
     * @var \DateTime|null
     * @ORM\Column(type="date", nullable=true)
     * @Serializer\Groups({"exceptions", "exception"})
     */
    private $date;

    /**
     * @var Reservable
     * @ORM\ManyToOne(targetEntity="Comsa\BookingBundle\Entity\Reservable")
     * @ORM\JoinColumn(name="reservable_id", referencedColumnName="id", nullable=true)
     */
    private $reservable;

    /**
     * @var int|null
     * @ORM\Column(type="integer", nullable=true)
     * @Serializer\Groups({"exceptions", "exception"})
     */
    private $day;

    /**
     * @var \DateTime|null
     * @ORM\Column(type="datetime", nullable=true)
     * @Serializer\Groups({"exceptions", "exception"})
     */
    private $activeFrom;

    /**
     * @var \DateTime|null
     * @ORM\Column(type="datetime", nullable=true)
     * @Serializer\Groups({"exceptions", "exception"})
     */
    private $activeTill;

    /**
     * @var Collection
     * @ORM\ManyToMany(targetEntity="Comsa\BookingBundle\Entity\ReservableInterval", inversedBy="exceptions")
     * @ORM\JoinTable(name="booking_reservations_exceptions_intervals")
     * @Serializer\Groups({"exception"})
     */
    private $intervals;

    /**
     * ReservationException constructor.
     */
    public function __construct()
    {
        $this->intervals = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return \DateTime|null
     */
    public function getDate(): ?\DateTime
    {
        return $this->date;
    }

    /**
     * @param \DateTime|null $date
     */
    public function setDate(?\DateTime $date): void
    {
        $this->date = $date;
    }

    /**
     * @return int|null
     */
    public function getDay(): ?int
    {
        return $this->day;
    }

    /**
     * @param int|null $day
     */
    public function setDay(?int $day): void
    {
        $this->day = $day;
    }

    /**
     * @return Collection
     */
    public function getIntervals(): Collection
    {
        return $this->intervals;
    }

    /**
     * @param Collection $intervals
     */
    public function setIntervals(Collection $intervals): void
    {
        $this->intervals = $intervals;
    }

    /**
     * @return Reservable
     */
    public function getReservable(): Reservable
    {
        return $this->reservable;
    }

    /**
     * @param Reservable $reservable
     */
    public function setReservable(Reservable $reservable): void
    {
        $this->reservable = $reservable;
    }

    /**
     * @return \DateTime|null
     */
    public function getActiveFrom(): ?\DateTime
    {
        return $this->activeFrom;
    }

    /**
     * @param \DateTime|null $activeFrom
     */
    public function setActiveFrom(?\DateTime $activeFrom): void
    {
        $this->activeFrom = $activeFrom;
    }

    /**
     * @return \DateTime|null
     */
    public function getActiveTill(): ?\DateTime
    {
        return $this->activeTill;
    }

    /**
     * @param \DateTime|null $activeTill
     */
    public function setActiveTill(?\DateTime $activeTill): void
    {
        $this->activeTill = $activeTill;
    }

    /**
     * @return string
     * @Serializer\VirtualProperty()
     * @Serializer\Groups({"exceptions"})
     */
    public function getSummary()
    {
        if ($this->intervals->isEmpty()) {
            return 'Hele dag';
        }

        return implode(';', $this->intervals->toArray());
    }

    /**
     * @Serializer\VirtualProperty()
     * @Serializer\Groups({"exceptions"})
     */
    public function getRules()
    {
        if ($this->getDate()) {
            return $this->getDate()->format('Y-m-d');
        }

        if ($this->getDay()) {
            return BookingManager::DAYS[$this->getDay() - 1];
        }
    }
}
