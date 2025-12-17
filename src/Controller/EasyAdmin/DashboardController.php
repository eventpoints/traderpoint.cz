<?php

namespace App\Controller\EasyAdmin;

use App\Entity\AdvertisingCampaign;
use App\Entity\Engagement;
use App\Entity\Feedback;
use App\Entity\TrackedLink;
use App\Entity\TrackedLinkClick;
use App\Entity\TraderProfile;
use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;

#[AdminDashboard(routePath: '/easy-admin', routeName: 'easy_admin')]
class DashboardController extends AbstractDashboardController
{
    public function index(): Response
    {
        $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);
        return $this->redirect($adminUrlGenerator->setController(UserCrudController::class)->generateUrl());
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('traderpoint.cz');
    }
    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
        yield MenuItem::linkToCrud('Engagement', 'fas fa-list', Engagement::class);
        yield MenuItem::linkToCrud('User', 'fas fa-list', User::class);
        yield MenuItem::linkToCrud('Trader Profile', 'fas fa-list', TraderProfile::class);
        yield MenuItem::linkToCrud('Feedback', 'fas fa-comment', Feedback::class);
        yield MenuItem::linkToCrud('Advertising Campaign', 'fas fa-list', AdvertisingCampaign::class);
        yield MenuItem::linkToCrud('Tracked Link', 'fas fa-list', TrackedLink::class);
        yield MenuItem::linkToCrud('Tracked Link Clicks', 'fas fa-list', TrackedLinkClick::class);
    }
}
