<?php

namespace App\Controller\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/phone-number')]

class PhoneNumberController extends AbstractController
{
    #[Route(path: '/create', name: 'create_phone_number')]
    public function create(): Response
    {
        $this->render('phone-number/create.html.twig');
    }
}