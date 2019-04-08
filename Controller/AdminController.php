<?php
namespace Comsa\BookingBundle\Controller;


use Comsa\BookingBundle\Entity\Option;
use Comsa\BookingBundle\Entity\Reservation;
use Comsa\BookingBundle\Repository\OptionRepository;
use Comsa\BookingBundle\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

class AdminController extends AbstractController
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

    public function createOption(Request $request, EntityManagerInterface $entityManager)
    {
        $requestContent = json_decode($request->getContent(), true);
        $option = new Option();
        $option->setTitle($requestContent['title']);
        if (isset($requestContent['locale'])) {
            $option->setTranslatableLocale($requestContent['locale']);
        }

        $entityManager->persist($option);
        $entityManager->flush();

        return new JsonResponse($option);
    }

    public function updateOption(Request $request, EntityManagerInterface $entityManager)
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

        $entityManager->persist($option);
        $entityManager->flush();

        return new JsonResponse($option);
    }

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
     */
    public function reservation(int $id, SerializerInterface $serializer, ReservationRepository $reservationRepository)
    {
        $reservation = $reservationRepository->find($id);
        return new Response($serializer->serialize($reservation, 'json', SerializationContext::create()->setGroups(['reservation'])));
    }
}
