<?php

namespace App\Controller\Controller;

use App\Data\FaqData;
use App\Entity\User;
use App\Repository\SkillRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class AppController extends AbstractController
{
    public function __construct(
        private readonly SkillRepository $skillRepository
    )
    {
    }

    #[Route(path: '/', name: 'landing')]
    public function landing(Request $request, #[CurrentUser] null|User $currentUser): Response
    {
        if ($currentUser instanceof User) {
            if ($currentUser->isTrader()) {
                return $this->redirectToRoute('trader_dashboard');
            }
            return $this->redirectToRoute('client_dashboard');
        }

        $skills = $this->skillRepository->findPrimarySkillsForLandingPage();
        $faqs = FaqData::getItems();

        return $this->render('app/landing.html.twig', [
            'skills' => $skills,
            'faqs' => $faqs
        ]);
    }

    #[Route(path: '/terms-of-use', name: 'terms_of_use')]
    public function termsOfUse(): Response
    {
        return $this->render('app/terms-of-use.html.twig');
    }

    #[Route(path: '/about-us', name: 'about_us')]
    public function aboutUs(): Response
    {
        return $this->render('app/about-us.html.twig');
    }
}