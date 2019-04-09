<?php
namespace Comsa\BookingBundle\Controller;


use Comsa\BookingBundle\Entity\Option;
use Comsa\BookingBundle\Entity\Reservable;
use Comsa\BookingBundle\Entity\Reservation;
use Comsa\BookingBundle\Entity\ReservationOption;
use Comsa\BookingBundle\Repository\OptionRepository;
use Comsa\BookingBundle\Repository\ReservableRepository;
use Comsa\BookingBundle\Repository\ReservationOptionRepository;
use Comsa\BookingBundle\Repository\ReservationRepository;
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
        $reservable = $reservableRepository->find($id);
        $reservable = $em->merge($serializer->deserialize($request->getContent(), Reservable::class, 'json'));
        $em->flush();

        return new Response($serializer->serialize($reservable, 'json', SerializationContext::create()->setGroups(['reservable'])));
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
     * @param EntityManagerInterface $entityManager
     * @return JsonResponse
     * @Rest\Post("/options")
     */
    public function createOption(Request $request, EntityManagerInterface $entityManager)
    {
        $requestContent = json_decode($request->getContent(), true);
        $option = new Option();
        $option->setTitle($requestContent['title']);
        if (isset($requestContent['locale'])) {
            $option->setTranslatableLocale($requestContent['locale']);
        }
        $option->setPrice($requestContent['price']);

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
        if (isset($requestContent['locale'])) {
            $option->setTranslatableLocale($requestContent['locale']);
        }
        $option->setPrice($requestContent['price']);

        $entityManager->persist($option);
        $entityManager->flush();

        return new Response($serializer->serialize($option, 'json', SerializationContext::create()->setGroups(['option'])));
    }
}
