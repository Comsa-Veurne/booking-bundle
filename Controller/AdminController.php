<?php
namespace Comsa\BookingBundle\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class AdminController extends AbstractController
{
    public function index()
    {
        return $this->render('@ComsaBooking/admin.html.twig');
    }
}