<?php
namespace Comsa\BookingBundle\Controller;


use Comsa\BookingBundle\Entity\Option;
use Comsa\BookingBundle\Entity\Reservable;
use Comsa\BookingBundle\Entity\ReservableInterval;
use Comsa\BookingBundle\Entity\Reservation;
use Comsa\BookingBundle\Entity\ReservationException;
use Comsa\BookingBundle\Entity\ReservationOption;
use Comsa\BookingBundle\Repository\OptionRepository;
use Comsa\BookingBundle\Repository\ReservableRepository;
use Comsa\BookingBundle\Repository\ReservationExceptionRepository;
use Comsa\BookingBundle\Repository\ReservationOptionRepository;
use Comsa\BookingBundle\Repository\ReservationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

class AdminController extends AbstractFOSRestController
{
    private $params;

    /**
     * AdminController constructor.
     */
    public function __construct(ParameterBagInterface $params)
    {
        $this->params = $params;
    }

    public function index()
    {
        return $this->render('@ComsaBooking/admin.html.twig', [
            'theme' => [
                'assets_url' => $this->params->get('theme.assets_url')
            ]
        ]);
    }

    /**
     * @param ReservationRepository $reservationRepository
     * @param SerializerInterface $serializer
     * @return Reservation[]|Response
     * @Rest\Get("/reservations")
     */
    public function reservations(ReservationRepository $reservationRepository, SerializerInterface $serializer)
    {
        return new Response($serializer->serialize(
            $reservationRepository->findAllForOverview(),
            'json',
            SerializationContext::create()->setGroups(['reservation'])
        ));
    }

    /**
     * @param Reservation $reservation
     * @Rest\Get("/reservations/{id}")
     */
    public function reservation(int $id, SerializerInterface $serializer, ReservationRepository $reservationRepository)
    {
        $reservation = $reservationRepository->find($id);
        return new Response($serializer->serialize($reservation, 'json', SerializationContext::create()->setGroups(['reservation'])));
    }

    /**
     * @param Request $request
     * @Rest\Delete("/reservations/{id}")
     */
    public function deleteReservation($id, Request $request, EntityManagerInterface $entityManager)
    {
        $reservation = $entityManager->getReference(Reservation::class, $id);
        $entityManager->remove($reservation);
        $entityManager->flush();

        return new JsonResponse(null, Response::HTTP_OK);
    }

    /**
     * @param ReservableRepository $reservableRepository
     * @param SerializerInterface $serializer
     * @return Response
     * @Rest\Get("/reservables")
     */
    public function reservables(ReservableRepository $reservableRepository, SerializerInterface $serializer)
    {
        return new Response($serializer->serialize(
            $reservableRepository->findAll(),
            'json',
            SerializationContext::create()->setGroups(['reservable'])
        ));
    }

    /**
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @return JsonResponse
     * @Rest\Post("/reservables")
     */
    public function createReservable(Request $request, EntityManagerInterface $entityManager)
    {
        $requestContent = json_decode($request->getContent(), true);
        $reservable = new Reservable();
        $reservable->setTitle($requestContent['title']);
        $reservable->setCapacity($requestContent['capacity']);

        $reservableIntervals = [];
        foreach ($requestContent['reservableIntervals'] as $interval) {
            $intervalEntity = new ReservableInterval();
            $intervalEntity->setReservable($reservable);
            $intervalEntity->setTimeFrom((new \DateTime())->setTimestamp(strtotime($interval['timeFrom'])));
            $intervalEntity->setTimeTo((new \DateTime())->setTimestamp(strtotime($interval['timeTo'])));

            if (isset($interval['price']) && (float) $interval['price'] > 0) {
                $intervalEntity->setPrice($interval['price']);
            }
            if (isset($interval['pricePerPerson']) && (float) $interval['pricePerPerson'] > 0) {
                $intervalEntity->setPricePerPerson($interval['pricePerPerson']);
            }
            $reservableIntervals[] = $intervalEntity;
        }

        $reservable->setReservableIntervals($reservableIntervals);

        $entityManager->persist($reservable);
        $entityManager->flush();

        return new JsonResponse($reservable);
    }

    /**
     * @param Reservation $reservation
     * @Rest\Get("/reservables/{id}")
     */
    public function reservable(int $id, SerializerInterface $serializer, ReservableRepository $reservableRepository)
    {
        $reservable = $reservableRepository->find($id);
        return new Response($serializer->serialize($reservable, 'json', SerializationContext::create()->setGroups(['reservable'])));
    }

    /**
     * @param int $id
     * @param ReservableRepository $reservableRepository
     * @param SerializerInterface $serializer
     * @param Request $request
     * @param EntityManagerInterface $em
     * @return Response
     * @Rest\Put("/reservables/{id}")
     */
    public function updateReservable(int $id, ReservableRepository $reservableRepository, SerializerInterface $serializer, Request $request, EntityManagerInterface $em)
    {
        $requestContent = json_decode($request->getContent());

        /** @var Reservable $reservable */
        $reservable = $em->getReference(Reservable::class, $id);
        $reservable->setTitle($requestContent->title);
        $reservable->setCapacity($requestContent->capacity);

        foreach ($requestContent->reservableIntervals as $interval) {
            if (isset($interval->id)) {
                $intervalEntity = $em->getReference(ReservableInterval::class, $interval->id);
            } else {
                $intervalEntity = new ReservableInterval();
                $intervalEntity->setReservable($reservable);
            }
            $intervalEntity->setTimeTo((new \DateTime())->setTimestamp(strtotime($interval->timeTo)));
            $intervalEntity->setTimeFrom((new \DateTime())->setTimestamp(strtotime($interval->timeFrom)));
            if (isset($interval->price) && $interval->price > 0) {
                $intervalEntity->setPrice((float) $interval->price);
            } else {
                $intervalEntity->setPrice(null);
            }
            if (isset($interval->pricePerPerson) && $interval->pricePerPerson > 0) {
                $intervalEntity->setPricePerPerson((float) $interval->pricePerPerson);
            } else {
                $intervalEntity->setPricePerPerson(null);
            }

            if (!isset($interval->id)) {
                $reservable->addReservableInterval($intervalEntity);
            }
        }

        $em->flush();

        return new Response($serializer->serialize($reservable, 'json', SerializationContext::create()->setGroups(['reservable'])));
    }

    /**
     * @param Request $request
     * @Rest\Delete("/reservables/{id}")
     */
    public function deleteReservable($id, Request $request, EntityManagerInterface $entityManager)
    {
        $item = $entityManager->getReference(Reservable::class, $id);
        $entityManager->remove($item);
        $entityManager->flush();

        return new JsonResponse(null, Response::HTTP_OK);
    }

    /**
     * @Rest\Get("/exceptions/{id}")
     */
    public function exception(int $id, SerializerInterface $serializer, ReservationExceptionRepository $exceptionRepository)
    {
        $exception = $exceptionRepository->find($id);
        return new Response($serializer->serialize($exception, 'json', SerializationContext::create()->setGroups(['exception'])));
    }

    /**
     * @param Reservation $reservation
     * @Rest\Get("/options/{id}")
     */
    public function option(int $id, SerializerInterface $serializer, OptionRepository $optionRepository)
    {
        $option = $optionRepository->find($id);
        return new Response($serializer->serialize($option, 'json', SerializationContext::create()->setGroups(['option'])));
    }

    /**
     * @param Request $request
     * @Rest\Delete("/options/{id}")
     */
    public function deleteOption($id, Request $request, EntityManagerInterface $entityManager)
    {
        $option = $entityManager->getReference(Option::class, $id);
        $entityManager->remove($option);
        $entityManager->flush();

        return new JsonResponse(null, Response::HTTP_OK);
    }

    /**
     * @param SerializerInterface $serializer
     * @param OptionRepository $optionRepository
     * @return Response
     * @Rest\Get("/options")
     */
    public function options(SerializerInterface $serializer, OptionRepository $optionRepository)
    {
        return new Response($serializer->serialize($optionRepository->findAll(), 'json', SerializationContext::create()->setGroups(['option'])));
    }

    /**
     * @param Request $request
     * @Rest\Delete("/exceptions/{id}")
     */
    public function deleteException($id, Request $request, EntityManagerInterface $entityManager)
    {
        $exception = $entityManager->getReference(ReservationException::class, $id);
        $entityManager->remove($exception);
        $entityManager->flush();

        return new JsonResponse(null, Response::HTTP_OK);
    }

    /**
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @Rest\Post("/exceptions")
     */
    public function createException(Request $request, EntityManagerInterface $entityManager, SerializerInterface $serializer)
    {
        $requestContent = json_decode($request->getContent(), true);
        $exception = new ReservationException();

        if (isset($requestContent['date'])) {
            $exception->setDate((new \DateTime())->setTimestamp(strtotime($requestContent['date'])));
        }

        if (isset($requestContent['day'])) {
            $exception->setDay($requestContent['day']);
        }

        if (isset($requestContent['activeFrom'])) {
            $exception->setActiveFrom((new \DateTime())->setTimestamp(strtotime($requestContent['availableFrom'])));
        }

        if (isset($requestContent['activeTill'])) {
            $exception->setActiveTill((new \DateTime())->setTimestamp(strtotime($requestContent['activeTill'])));
        }

        if (isset($requestContent['intervals']) && is_array($requestContent['intervals'])) {
            $intervals = new ArrayCollection();
            foreach ($requestContent['intervals'] as $intervalId) {
                $interval = $entityManager->getRepository(ReservableInterval::class)->find($intervalId);
                $intervals->add($interval);
            }
            $exception->setIntervals($intervals);
        }

        $entityManager->persist($exception);
        $entityManager->flush();

        return new Response($serializer->serialize($exception, 'json', SerializationContext::create()->setGroups(['exception'])));
    }

    /**
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @param SerializerInterface $serializer
     * @return Response
     * @Rest\Put("/exceptions/{id}")
     */
    public function updateException(Request $request, EntityManagerInterface $entityManager, SerializerInterface $serializer)
    {
        $requestContent = json_decode($request->getContent(), true);
        //-- Find exception
        $reservationExceptionRepository = $entityManager->getRepository(ReservationException::class);
        $exception = $reservationExceptionRepository->find($requestContent['id']);

        if (!$exception instanceof ReservationException) {
            throw new ResourceNotFoundException();
        }

        if (isset($requestContent['date'])) {
            $exception->setDate((new \DateTime())->setTimestamp(strtotime($requestContent['date'])));
        }

        if (isset($requestContent['day'])) {
            $exception->setDay($requestContent['day']);
        }

        if (isset($requestContent['availableTill'])) {
            $exception->setActiveFrom((new \DateTime())->setTimestamp(strtotime($requestContent['availableTill'])));
        }

        if (isset($requestContent['availableFrom'])) {
            $exception->setActiveTill((new \DateTime())->setTimestamp(strtotime($requestContent['availableFrom'])));
        }

        if (isset($requestContent['intervals']) && is_array($requestContent['intervals'])) {
            $intervals = new ArrayCollection();
            foreach ($requestContent['intervals'] as $intervalId) {
                $interval = $entityManager->getRepository(ReservableInterval::class)->find($intervalId);
                $intervals->add($interval);
            }
            $exception->setIntervals($intervals);
        }

        $entityManager->persist($exception);
        $entityManager->flush();

        return new Response($serializer->serialize($exception, 'json', SerializationContext::create()->setGroups(['exception'])));
    }

    /**
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @return JsonResponse
     * @Rest\Post("/options")
     */
    public function createOption(Request $request, EntityManagerInterface $entityManager)
    {
        $requestContent = json_decode($request->getContent(), true);
        $option = new Option();
        $option->setTitle($requestContent['title']);
        $option->setDescription($requestContent['description']);
        if (isset($requestContent['locale'])) {
            $option->setTranslatableLocale($requestContent['locale']);
        }
        $option->setPrice($requestContent['price']);
        foreach ($requestContent['intervals'] as $intervalId) {
            $interval = $entityManager->getRepository(ReservableInterval::class)->find($intervalId);
            $option->addInterval($interval);
        }

        $entityManager->persist($option);
        $entityManager->flush();

        return new JsonResponse($option);
    }

    /**
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @param SerializerInterface $serializer
     * @return Response
     * @Rest\Put("/options/{id}")
     */
    public function updateOption(Request $request, EntityManagerInterface $entityManager, SerializerInterface $serializer)
    {
        $requestContent = json_decode($request->getContent(), true);
        //-- Find option
        /** @var OptionRepository $optionRepository */
        $optionRepository = $entityManager->getRepository(Option::class);
        $option = $optionRepository->find($requestContent['id']);

        if (!$option instanceof Option) {
            throw new ResourceNotFoundException();
        }

        $option->setTitle($requestContent['title']);
        $option->setDescription($requestContent['description']);
        if (isset($requestContent['locale'])) {
            $option->setTranslatableLocale($requestContent['locale']);
        }
        $option->setPrice($requestContent['price']);

        if (isset($requestContent['intervals']) && is_array($requestContent['intervals'])) {
            $intervals = new ArrayCollection();
            foreach ($requestContent['intervals'] as $intervalId) {
                /** @var ReservableInterval $interval */
                $interval = $entityManager->getRepository(ReservableInterval::class)->find($intervalId);
                $intervals->add($interval);

                if (!$option->getIntervals()->contains($interval)) {
                    $option->addInterval($interval);
                }
            }

            foreach ($option->getIntervals() as $existingInterval) {
                if (!$intervals->contains($existingInterval)) {
                    $option->removeInterval($existingInterval);
                }
            }
        }

        $entityManager->persist($option);
        $entityManager->flush();

        return new Response($serializer->serialize($option, 'json', SerializationContext::create()->setGroups(['option'])));
    }

    /**
     * @param SerializerInterface $serializer
     * @param ReservationExceptionRepository $exceptionRepository
     * @return Response
     * @Rest\Get("/exceptions")
     */
    public function exceptions(SerializerInterface $serializer, ReservationExceptionRepository $exceptionRepository)
    {
        return new Response($serializer->serialize($exceptionRepository->findAll(), 'json'));
    }
}
