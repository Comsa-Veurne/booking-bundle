<?php

namespace Comsa\BookingBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class FrontController extends AbstractController
{
    public function index()
    {
        return $this->render('@ComsaBooking/front.html.twig', [
            'controller_name' => 'FrontController',
        ]);
    }
}
