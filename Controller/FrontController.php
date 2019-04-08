<?php

namespace Comsa\BookingBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Annotation\Route;

class FrontController extends AbstractController
{
    private $params;

    /**
     * FrontController constructor.
     */
    public function __construct(ParameterBagInterface $params)
    {
        $this->params = $params;
    }

    public function index()
    {
        return $this->render('@ComsaBooking/front.html.twig', [
            'theme' => [
                'assets_url' => $this->params->get('theme.assets_url')
            ],
        ]);
    }
}
