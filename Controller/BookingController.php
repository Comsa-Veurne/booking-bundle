<?php

namespace Comsa\BookingBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class BookingController extends AbstractController
{
    public function disabledDates()
    {
        return new JsonResponse([
            (\DateTime::createFromFormat('Y/m/d', '2019/04/01'))->format('Y-m-d'),
            \DateTime::createFromFormat('Y/m/d', '2019/04/02')->format('Y-m-d')
        ]);
    }

    public function options(Request $request)
    {
        $requestContent = json_decode($request->getContent(), true);
        return new JsonResponse([
            [
                'id' => 1,
                'name' => 'Broodmand',
                'price' => 10
            ],
            [
                'id' => 2,
                'name' => 'Cola',
                'price' => 2.5
            ],
            [
                'id' => 3,
                'name' => 'Demo',
                'price' => $requestContent['date']
            ]
        ]);
    }

    public function createBooking(Request $request) {
        $requestContent = json_decode($request->getContent(), true);
        return new JsonResponse($requestContent);
    }
}
