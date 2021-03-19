<?php


namespace Comsa\BookingBundle\Controller;


use Comsa\BookingBundle\Entity\Payment;
use Comsa\BookingBundle\Entity\PaymentMethod;
use Comsa\BookingBundle\Manager\BookingManager;
use Comsa\BookingBundle\PaymentMethods\MollieType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PaymentController extends AbstractController
{
    //-- Declare typehandler service in container, so this container can find the service
    //-- Typehandler will always be Mollie (only one paymentMethod in this application)
    public static function getSubscribedServices()
    {
        return array_merge(
            parent::getSubscribedServices(), [
                'comsa_booking_payment_method_mollie' => MollieType::class,
            ]
        );
    }


    public function handle(BookingManager $bookingManager)
    {

        $reservation = $bookingManager->get('reservation');

        //-- Create new payment
        $payment = new Payment();
        $payment->setReservation($reservation);
        //-- Price is always 50 (pre-determined)
        $payment->setAmount(50.00);

        $this->getDoctrine()->getManager()->persist($payment);
        $this->getDoctrine()->getManager()->flush();

        //-- Get typehandler service
        $typeHandler = $this->get('comsa_booking_payment_method_mollie');

        return $typeHandler->handlePayment($payment);

    }

    public function handleRedirect(BookingManager $bookingManager)
    {
        $typeHandler = $this->get('comsa_booking_payment_method_mollie');

        //-- Redirect to Thank You page
        return $typeHandler->afterPayment();
    }

    public function webhook(Request $request, EventDispatcherInterface $eventDispatcher)
    {
        //-- Get mollie id given through a post request
        $id = $request->request->get('id');

        //-- find payment by externalId === mollie id
        $payment = $this->getDoctrine()->getRepository(Payment::class)->findOneBy([
           'externalId' => $id
        ]);

        $typeHandler = $this->get('comsa_booking_payment_method_mollie');

        //-- Check payment status through webhook
        $isPaid = $typeHandler->checkPayment($payment);

        if (!$isPaid){
            return new Response('Status has not changed', Response::HTTP_OK);
        }

        //-- If checkpayment returns true, set paymentCompletedAt to current datetime
        $payment->setPaid();
        $this->getDoctrine()->getManager()->flush();

        //-- Trigger email event
        $typeHandler->afterSuccessWebhook($payment);

        return new Response('Status has been updated', Response::HTTP_OK);
    }
}
