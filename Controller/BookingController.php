<?php

namespace Comsa\BookingBundle\Controller;

use Comsa\BookingBundle\Entity\Address;
use Comsa\BookingBundle\Entity\Reservable;
use Comsa\BookingBundle\Entity\ReservableInterval;
use Comsa\BookingBundle\Entity\Reservation;
use Comsa\BookingBundle\Entity\ReservationOption;
use Comsa\BookingBundle\Manager\BookingManager;
use Comsa\BookingBundle\Repository\OptionRepository;
use Comsa\BookingBundle\Repository\ReservableIntervalRepository;
use Comsa\BookingBundle\Repository\ReservableRepository;
use Comsa\BookingBundle\Repository\ReservationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;

class BookingController extends AbstractController
{
    /**
     * @var OptionRepository
     */
    private $optionRepository;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * BookingController constructor.
     */
    public function __construct(OptionRepository $optionRepository, EntityManagerInterface $em)
    {
        $this->optionRepository = $optionRepository;
        $this->em = $em;
    }

    public function disabledDates(Request $request, ReservationRepository $reservationRepository, ReservableRepository $reservableRepository, EntityManagerInterface $em, BookingManager $bookingManager): JsonResponse
    {
        $requestContent = json_decode($request->getContent(), true);
        $reservableId = $requestContent['reservable'];
        $amountPersons = $requestContent['amountPersons'];

        $reservations = $reservationRepository->findAll();

        if ($reservableId) {
            //-- Specific to reservable
            $reservable = $reservableRepository->find($reservableId);
            $disabledDates = $bookingManager->getDisabledDatesForReservable($reservable);
        } else {
            //-- Not specific to reservable (uses amount of persons)
            $disabledDates = $bookingManager->getDisabledDatesForAmountPersons($amountPersons);
        }
        return new JsonResponse($disabledDates);
    }

    public function reservables(Request $request, ReservableRepository $reservableRepository, SerializerInterface $serializer)
    {
        $requestContent = json_decode($request->getContent(), true);
        $amountPersons = $requestContent['amountPersons'];
        $reservables = $reservableRepository->findSuitableReservable($amountPersons);
        return new Response($serializer->serialize($reservables, 'json', SerializationContext::create()->setGroups([
            'groups' => 'reservable'
        ])));
    }

    public function intervals(Request $request, ReservableRepository $reservableRepository, ReservableIntervalRepository $intervalRepository, SerializerInterface $serializer, BookingManager $bookingManager)
    {
        $requestContent = json_decode($request->getContent(), true);
        $reservableId = isset($requestContent['reservable']) ? $requestContent['reservable'] : 0;
        $date = (new \DateTime($requestContent['date']))->setTime(0, 0, 0, 0);
        $amountPersons = $requestContent['amountPersons'];

        if ($reservableId) {
            $reservable = $reservableRepository->find($reservableId);
            $intervals = $bookingManager->getIntervalsForReservableOnDate($reservable, $date);
        } else {
            $intervals = new ArrayCollection();
            /** @var Reservable $reservable */
            foreach ($reservableRepository->findSuitableReservable($amountPersons) as $reservable) {
                $intervalsForReservable = $bookingManager->getIntervalsForReservableOnDate($reservable, $date);
                $intervals = new ArrayCollection($intervals->toArray() + $intervalsForReservable->toArray());
            }
        }

        $returnIntervals = [];
        /** @var ReservableInterval $interval */
        foreach ($intervals as $interval) {
            $returnIntervals[] = [
                'from' => $interval->getTimeFrom()->format('H:i'),
                'to' => $interval->getTimeTo()->format('H:i'),
                'id' => $interval->getId()
            ];
        }

        return new JsonResponse($returnIntervals);
    }

    public function maxPersons(ReservableRepository $reservableRepository)
    {
        return new JsonResponse($reservableRepository->findHighestCapacity());
    }

    public function options(Request $request, SerializerInterface $serializer)
    {
        $requestContent = json_decode($request->getContent(), true);
        return new Response($serializer->serialize((array) $this->optionRepository->findAll(), 'json'));
    }

    public function createBooking(Request $request, SerializerInterface $serializer) {
        $requestContent = json_decode($request->getContent(), true);
        //-- Transfer json into a reservation entity
        $reservation = new Reservation();

        //-- Try to find address by email
        $address = $this->em->getRepository(Address::class)->findOneByEmail($requestContent['information']['email']);
        if (!$address instanceof Address) {
            $address = new Address();
            $address->setName($requestContent['information']['name']);
            $address->setEmail($requestContent['information']['email']);
            $address->setStreet($requestContent['information']['street']);
            $address->setPostal($requestContent['information']['postal']);
            $address->setCity($requestContent['information']['city']);
            $address->setStreetNumber($requestContent['information']['streetNumber']);
        }

        $reservation->setAddress(
            $address
        );
        $reservation->setAmountPersons($requestContent['amountPersons']);

        $date = (new \DateTime($requestContent['date']))->format('Y-m-d');

        $intervalId = $requestContent['interval']['id'];
        $interval = $this->em->getRepository(ReservableInterval::class)->find($intervalId);
        $reservation->setReservableInterval($interval);
        $reservation->setReservable($interval->getReservable());
        $reservation->setDate(new \DateTime($date));

        foreach ($requestContent['options'] as $option) {
            $reservationOption = new ReservationOption();
            $reservationOption->setLinkedOption($this->optionRepository->find($option['id']));
            $reservationOption->setReservation($reservation);
            $reservationOption->setTimes($option['times']);

            if ($reservationOption->isValid()) {
                $reservation->addReservationOption(
                    $reservationOption
                );
            }
        }

        $this->em->persist($reservation);
        $this->em->flush();
        return new Response($serializer->serialize($reservation, 'json', SerializationContext::create()->setGroups([
            'groups' => 'reservation'
        ])));
    }
}
