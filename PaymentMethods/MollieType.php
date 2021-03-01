<?php

namespace Comsa\BookingBundle\PaymentMethods;

use Comsa\BookingBundle\Entity\Payment;
use Comsa\BookingBundle\Entity\Reservation;
use Comsa\BookingBundle\Event\ReservationCreated;
use Doctrine\ORM\EntityManagerInterface;
use Illuminate\Support\Facades\Redirect;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Mollie\Api\MollieApiClient;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;

class MollieType
{
    /**
     * @var ParameterBagInterface
     */
    private $parameterbag;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var MollieApiClient
     */
    private $mollie;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    public function __construct(ParameterBagInterface $parameterBag, RouterInterface $router, EntityManagerInterface $entityManager, EventDispatcherInterface $eventDispatcher)
    {
        $this->parameterbag = $parameterBag;
        $this->router = $router;
        $this->entityManager = $entityManager;
        $this->eventDispatcher = $eventDispatcher;

        $this->mollie = new MollieApiClient();
        $this->mollie->setApiKey($this->parameterbag->get('comsa_booking_payment_method_mollie'));
    }

    public function handlePayment(Payment $payment): Response
    {
        $molliePayment = $this->mollie->payments->create([
           'amount' => [
             'currency' => 'EUR',
             'value' => number_format($payment->getAmount(),2)
           ],
           'description' => "Reservatie Hooipiet #{$payment->getReservation()->getId()}",
           'redirectUrl' => $this->router->generate('booking_handle_payment_redirect', [], RouterInterface::ABSOLUTE_URL),
            //-- Test local webhook by exposing local with lt --port 8000
          // 'webhookUrl' => ' https://shy-turtle-64.loca.lt/webhook-payment'

          'webhookUrl' => $this->router->generate('booking_webhook_payment')
        ]);

        //-- Set mollie id as external id
        $payment->setExternalId($molliePayment->id);
        $this->entityManager->flush();

        //-- Redirect to molliepage
        return new RedirectResponse($molliePayment->getCheckoutUrl());
    }

    public function afterPayment()
    {
        //-- Redirect to thank you page
        return new RedirectResponse($this->router->generate('booking_thanks'));
    }

    public function checkPayment(Payment $payment): bool
    {
        //-- check status of mollie payment and return true or false
        $molliePayment = $this->mollie->payments->get($payment->getExternalId());
        return $molliePayment->isPaid();
    }

    public function afterSuccessWebhook(Payment $payment): void
    {
        //-- Trigger event after successfullwebhook
        $this->confirmReservation();
        $this->fullfillReservation($payment->getReservation());
    }

    protected function confirmReservation(Reservation $reservation)
    {
        $event = new ReservationCreated($reservation);
        $this->eventDispatcher->dispatch(ReservationCreated::NAME, $event);
    }
}
