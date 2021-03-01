<?php


namespace Comsa\BookingBundle\Entity;


use Comsa\BookingBundle\Entity\Traits\TimestampableTrait;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="booking_payment")
 * @ORM\HasLifecycleCallbacks()
 */
class Payment
{
    use TimestampableTrait;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\OneToOne(targetEntity="Comsa\BookingBundle\Entity\Reservation", inversedBy="payment")
     */
    private $reservation;

    /**
     * @ORM\Column(type="decimal", precision=10, scale=2)
     */
    private $amount;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $externalId;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $paymentCompletedAt;

    public function getReservation(): Reservation
    {
        return $this->reservation;
    }


    public function setReservation(Reservation $reservation)
    {
        $this->reservation = $reservation;
    }

    public function getAmount()
    {
        return $this->amount;
    }

    public function setAmount($amount): void
    {
        $this->amount = $amount;
    }

    public function getExternalId()
    {
        return $this->externalId;
    }

    public function setExternalId($externalId): void
    {
        $this->externalId = $externalId;
    }

    public function getPaymentCompletedAt()
    {
        return $this->paymentCompletedAt;
    }

    public function setPaid()
    {
        $this->paymentCompletedAt = new \DateTime();
    }


}
