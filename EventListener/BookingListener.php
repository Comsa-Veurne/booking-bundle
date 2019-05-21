<?php
/**
 * Created by PhpStorm.
 * User: cirykpopeye
 * Date: 2019-05-20
 * Time: 10:09
 */

namespace Comsa\BookingBundle\EventListener;


use Comsa\BookingBundle\Entity\Reservation;
use Comsa\BookingBundle\Event\ReservationCreated;
use JMS\Serializer\Serializer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;
use Twig\Environment;

class BookingListener
{
    private $mailer;
    private $twig;
    private $serializer;

    public function __construct(\Swift_Mailer $mailer, Environment $twig, Serializer $serializer)
    {
        $this->mailer = $mailer;
        $this->twig = $twig;
        $this->serializer = $serializer;
    }

    public function onReservationCreated(ReservationCreated $event)
    {
        $messageClient = (new \Swift_Message('Bedankt voor uw reservatie.'))
            ->setFrom('cms@comsa.be')
            ->setTo($event->getReservation()->getAddress()->getEmail())
            ->setBody(
                $this->twig->render('@ComsaBooking/mail/thanks.html.twig', [
                    'booking' => $event->getReservation()
                ]),
                'text/html'
            );

        $messageAdmin = (new \Swift_Message('Nieuwe reservatie via booking module.'))
            ->setFrom('cms@comsa.be')
            ->setTo('ciryk@comsa.be')
            ->setBody(
                $this->twig->render('@ComsaBooking/mail/reservation.html.twig', [
                    'booking' => $event->getReservation()
                ]),
                'text/html'
            );

        $this->mailer->send($messageAdmin);
        $this->mailer->send($messageClient);
    }
}
