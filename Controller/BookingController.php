<?php

namespace Comsa\BookingBundle\Controller;

use Comsa\BookingBundle\Entity\Address;
use Comsa\BookingBundle\Entity\Reservable;
use Comsa\BookingBundle\Entity\ReservableInterval;
use Comsa\BookingBundle\Entity\Reservation;
use Comsa\BookingBundle\Entity\ReservationOption;
use Comsa\BookingBundle\Event\ReservationCreated;
use Comsa\BookingBundle\Manager\BookingManager;
use Comsa\BookingBundle\Repository\OptionRepository;
use Comsa\BookingBundle\Repository\ReservableIntervalRepository;
use Comsa\BookingBundle\Repository\ReservableRepository;
use Comsa\BookingBundle\Repository\ReservationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;

/**
 * Class BookingController
 * @package Comsa\BookingBundle\Controller
 */
class BookingController extends AbstractFOSRestController
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
     * @var SessionInterface
     */
    private $session;

    /**
     * BookingController constructor.
     */
    public function __construct(OptionRepository $optionRepository, EntityManagerInterface $em, SessionInterface $session)
    {
        $this->optionRepository = $optionRepository;
        $this->em = $em;
        $this->session = $session;
    }

    /**
     * @param Request $request
     * @param ReservationRepository $reservationRepository
     * @param ReservableRepository $reservableRepository
     * @param EntityManagerInterface $em
     * @param BookingManager $bookingManager
     * @return JsonResponse
     * @Rest\Post("/disabled-dates")
     */
    public function disabledDates(Request $request, ReservationRepository $reservationRepository, ReservableRepository $reservableRepository, EntityManagerInterface $em, BookingManager $bookingManager): JsonResponse
    {
        $requestContent = json_decode($request->getContent(), true);
        $reservables = $requestContent['reservables'];
        $amountPersons = $requestContent['amountPersons'];
        $firstDayOfMonth = (new \DateTime())->setTimestamp(strtotime($requestContent['firstDayOfMonth']));
        $firstDayOfMonth->setTime(0, 0, 0, 0);

        $dayRange = [];
        $daysInMonth = cal_days_in_month(
            CAL_GREGORIAN,
            (int) $firstDayOfMonth->format('n'),
            (int) $firstDayOfMonth->format('Y')
        );

        for ($i = 1; $i <= $daysInMonth; $i++) {
            $tmpDate = clone $firstDayOfMonth;
            $tmpDate->setDate($firstDayOfMonth->format('Y'), $firstDayOfMonth->format('n'), $i);
            $dayRange[] = $tmpDate;
            unset($tmpDate);
        }

        if (count($reservables) === 1) {
            //-- Doesn't matter which reservable
            //-- Fetch disabled dates for all reservables, but if not in one date should be disabled
            $disabledDatesCollection = [];
            $reservables = $reservableRepository->findSuitableReservables($amountPersons);
            $first = true;
            foreach ($reservables as $reservable) {
                $disabledDatesForReservable = $bookingManager->getDisabledDatesForReservable($reservable, $dayRange);
                if ($first) {
                    $disabledDatesCollection = $disabledDatesForReservable;
                    $first = false;
                    continue;
                }

                foreach ($disabledDatesCollection as $key => $disabledDate) {
                    if (!in_array($disabledDate, $disabledDatesForReservable)) {
                        unset($disabledDatesCollection[$key]);
                    }
                }
            }
            $disabledDatesCollection = array_values($disabledDatesCollection);
            return new JsonResponse(array_values((array) array_unique($disabledDatesCollection)));
        }

        //-- Certain match was given
        //-- Fetch disabled dates for all reservables
        $disabledDatesCollection = [];
        foreach ($reservables as $reservable) {
            $reservableEntity = $em->getReference(Reservable::class, $reservable['id']);
            $disabledDatesCollection = array_merge($disabledDatesCollection, $bookingManager->getDisabledDatesForReservable($reservableEntity, $dayRange));
        }
        $disabledDatesCollection = array_values($disabledDatesCollection);
        return new JsonResponse(array_values((array) array_unique($disabledDatesCollection)));
    }

    /**
     * @param Request $request
     * @param ReservableRepository $reservableRepository
     * @param SerializerInterface $serializer
     * @return Response
     * @Rest\Post("/reservables-for-criteria")
     */
    public function reservables(Request $request, ReservableRepository $reservableRepository, SerializerInterface $serializer, ReservableIntervalRepository $reservableIntervalRepository)
    {
        $requestContent = json_decode($request->getContent(), true);

        //-- We have this amount of persons
        $amountPersons = $requestContent['amountPersons'];

        //-- Do we have a reservable that fits all?
        $reservableThatFitsAll = $reservableRepository->findSuitableReservable($amountPersons);

        if (!$reservableThatFitsAll) {
            $matches = $reservableIntervalRepository->findMatches();
            $match = $reservableRepository->findSufficientCapacityForMatches($matches, $amountPersons);

            $reservables = [];
            foreach ($match['intervals'] as $interval) {
                if (!in_array($interval->getReservable(), $reservables)) {
                    $reservables[] = $interval->getReservable();
                }
            }
        } else {
            $reservables = [
                $reservableThatFitsAll
            ];
        }

        $capacity = 0;
        $requiredReservables = new ArrayCollection();
        foreach ($reservables as $reservable) {
            $capacity += $reservable->getCapacity();
            $requiredReservables->add($reservable);
            if ($capacity >= $amountPersons) {
                break;
            }
        }
        return new Response($serializer->serialize($requiredReservables, 'json', SerializationContext::create()->setGroups([
            'groups' => 'reservable'
        ])));
    }

    /**
     * @param Request $request
     * @param ReservableRepository $reservableRepository
     * @param ReservableIntervalRepository $intervalRepository
     * @param SerializerInterface $serializer
     * @param BookingManager $bookingManager
     * @return JsonResponse
     * @throws \Exception
     * @Rest\Post("/intervals")
     */
    public function intervals(Request $request, ReservableRepository $reservableRepository, ReservableIntervalRepository $intervalRepository, SerializerInterface $serializer, BookingManager $bookingManager)
    {
        $requestContent = json_decode($request->getContent(), true);
        $reservables = $requestContent['reservables'];
        $date = (new \DateTime($requestContent['date']))->setTime(0, 0, 0, 0);
        $amountPersons = $requestContent['amountPersons'];

        //-- If only one reservable, it doesn't matter which one it is, if two it's a match so both are required and can't be changed
        if (count($reservables) === 1) {
            $reservables =  $reservableRepository->findSuitableReservables($amountPersons);
            $intervalCollection = [];
            foreach ($reservables as $reservable) {
                $intervals = $bookingManager->getIntervalsForReservableOnDate($reservable, $date);
                $intervalCollection = array_merge($intervalCollection, $intervals->toArray());
            }
        } else {
            $intervalCollection = [];
            foreach ($reservables as $reservable) {
                $reservableEntity = $reservableRepository->find($reservable['id']);
                $intervals = $bookingManager->getIntervalsForReservableOnDate($reservableEntity, $date);
                $intervalCollection = array_merge($intervals->toArray(), $intervalCollection);
            }

            /** @var ReservableInterval $intervalInCollection */
            foreach ($intervalCollection as $key => $intervalInCollection) {
                $found = false;
                /** @var ReservableInterval $intervalTmpInCollection */
                foreach ($intervalCollection as $intervalTmpInCollection) {
                    if (
                        $intervalTmpInCollection->getId() !== $intervalInCollection->getId() &&
                        $intervalTmpInCollection->getTimeTo()->format('H:i') === $intervalInCollection->getTimeTo()->format('H:i') &&
                        $intervalTmpInCollection->getTimeFrom()->format('H:i') === $intervalInCollection->getTimeFrom()->format('H:i')
                    ) {
                        $found = true;
                    }
                }

                if (!$found) {
                    unset($intervalCollection[$key]);
                }
            }
        }

        $returnIntervals = [];
        /** @var ReservableInterval $interval */
        foreach ($intervalCollection as $interval) {
            $identifier = $interval->getTimeFrom()->format('Hi') . $interval->getTimeTo()->format('Hi');

            if (isset($returnIntervals[$identifier])) {
                $returnIntervals[$identifier]['ids'][] = $interval->getId();
            } else {
                $returnIntervals[$identifier] = [
                    'from' => $interval->getTimeFrom()->format('H:i'),
                    'to' => $interval->getTimeTo()->format('H:i'),
                    'ids' => [$interval->getId()],
                    'price' => $interval->getPrice(),
                    'pricePerPerson' => $interval->getPricePerPerson()
                ];
            }
        }

        $return = [];
        foreach ($returnIntervals as $interval) {
            if ($interval['pricePerPerson'] > 0) {
                $interval['activePrice'] = $interval['pricePerPerson'] * $amountPersons;
            } else {
                $interval['activePrice'] = $interval['price'];
            }
            $return[] = $interval;
        }


        usort($return, array('self','sortByInterval'));
        return new JsonResponse($return);
    }

    /**
     * @param ReservableRepository $reservableRepository
     * @return JsonResponse
     * @Rest\Get("/max-persons")
     */
    public function maxPersons(ReservableRepository $reservableRepository, ReservableIntervalRepository $reservableIntervalRepository)
    {
        //-- Get highest capacity of a reservable
        $singleReservableCapacity = $reservableRepository->findHighestCapacity();

        //-- Get matches
        $matches = $reservableIntervalRepository->findMatches();

        //-- Get highest capacity when reservables are matched
        $matchedReservableCapacity = $reservableRepository->findHighestCapacityForMatches($matches);

        return new JsonResponse([
            'single' => $singleReservableCapacity,
            'matched' => $matchedReservableCapacity
        ]);
    }

    /**
     * @param Request $request
     * @param SerializerInterface $serializer
     * @return Response
     * @Rest\Post("/options-by-criteria")
     */
    public function options(Request $request, SerializerInterface $serializer, EntityManagerInterface $em)
    {
        $requestContent = json_decode($request->getContent(), true);
        if (count($requestContent['interval']['ids']) > 1) {
            $options = new ArrayCollection();
            foreach ($requestContent['interval']['ids'] as $id) {
                $interval = $em->getReference(ReservableInterval::class, $id);
                $optionsForInterval = $this->optionRepository->findByCriteria($interval);
                foreach ($optionsForInterval as $optionForInterval) {
                    if (!$options->contains($optionForInterval)) {
                        $options->add($optionForInterval);
                    }
                }
            }
        } else {
            $interval = $em->getReference(ReservableInterval::class, $requestContent['interval']['ids'][0]);
            $options = $this->optionRepository->findByCriteria($interval);
        }
        return new Response($serializer->serialize($options, 'json', SerializationContext::create()->setGroups([
            'groups' => 'option'
        ])));
    }

    /**
     * @param Request $request
     * @param SerializerInterface $serializer
     * @return Response
     * @throws \Exception
     * @Rest\Post("/bookings")
     */
    public function createBooking(Request $request, SerializerInterface $serializer, EventDispatcherInterface $eventDispatcher) {
        $requestContent = json_decode($request->getContent(), true);
        //-- Transfer json into a reservation entity
        $reservation = new Reservation();

        //-- Try to find address by email
        $address = $this->em->getRepository(Address::class)->findOneByEmail($requestContent['information']['email']);
        if (!$address instanceof Address) {
            $address = new Address();
            $address->setName($requestContent['information']['name']);
            $address->setEmail($requestContent['information']['email']);
            $address->setPhone($requestContent['information']['phone']);
            $address->setStreet($requestContent['information']['street']);
            $address->setPostal($requestContent['information']['postal']);
            $address->setCity($requestContent['information']['city']);
            $address->setStreetNumber($requestContent['information']['streetNumber']);
        }

        $reservation->setAddress(
            $address
        );
        $reservation->setAmountPersons((int) $requestContent['amountPersons']);

        $date = (new \DateTime($requestContent['date']))->format('Y-m-d');

        $intervalIds = $requestContent['interval']['ids'];
        $intervals = new ArrayCollection();
        $reservables = new ArrayCollection();

        $capacity = 0;

        foreach ($intervalIds as $intervalId) {
            /** @var ReservableInterval $interval */
            $interval = $this->em->getRepository(ReservableInterval::class)->find($intervalId);
            $capacity += $interval->getReservable()->getCapacity();

            $intervals->add($interval);

            if ($capacity >= $reservation->getAmountPersons()) {
                break;
            }
        }

        foreach ($intervals as $interval) {
            if (!$reservables->contains($interval->getReservable())) {
                $reservables->add($interval->getReservable());
            }
        }

        $reservation->setReservableIntervals($intervals);
        $reservation->setReservables($reservables);
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
        $this->session->set('reservation', $reservation);

        //-- Dispatch event
//        $event = new ReservationCreated($reservation);
//        $eventDispatcher->dispatch(ReservationCreated::NAME, $event);

        return new Response($serializer->serialize($reservation, 'json', SerializationContext::create()->setGroups([
            'groups' => 'reservation'
        ])));
    }

    private static function sortByInterval($a, $b) {
        $aFrom = str_replace(':','',$a['from']);
        $bFrom = str_replace(':','',$b['from']);
        return $aFrom - $bFrom;
    }
}
