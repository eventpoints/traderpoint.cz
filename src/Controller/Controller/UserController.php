<?php

namespace App\Controller\Controller;

use App\Entity\Engagement;
use App\Entity\User;
use App\Enum\FlashEnum;
use App\Enum\UserRoleEnum;
use App\Form\Form\AccountFormType;
use App\Form\Form\TraderAccountFormType;
use App\Form\Form\UserNotificationSettingsType;
use App\Repository\EngagementRepository;
use App\Repository\TraderProfileRepository;
use App\Repository\UserRepository;
use App\Service\ImageOptimizer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\UX\Map\Bridge\Leaflet\LeafletOptions;
use Symfony\UX\Map\Bridge\Leaflet\Option\TileLayer;
use Symfony\UX\Map\Map;
use Symfony\UX\Map\Point;

#[Route(path: '/user')]
class UserController extends AbstractController
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly TraderProfileRepository $traderProfileRepository,
        private readonly ImageOptimizer $imageOptimizer,
        private readonly EngagementRepository $engagementRepository,
    )
    {
    }

    #[Route(path: '/account', name: 'user_account', methods: [Request::METHOD_GET, Request::METHOD_POST])]
    public function account(Request $request, #[CurrentUser] User $currentUser): Response
    {
        $tab = $request->query->get('tab', 'account');

        $traderAccountForm = null;
        if ($currentUser->isTrader()) {
            $traderProfile = $currentUser->getTraderProfile();
            $latitude = $traderProfile->getLatitude() ?: 50.07897895366278;
            $longitude = $traderProfile->getLongitude() ?: 14.430823454571573;
            $map = (new Map('default'))
                ->center(new Point($latitude, $longitude))
                ->zoom(11)
                ->options(
                    (new LeafletOptions())
                        ->tileLayer(new TileLayer(
                            url: 'https://api.maptiler.com/maps/streets-v2/{z}/{x}/{y}.png?key=1IDdEWmfCtjKNlJ6Ij3W',
                            attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
                            options: [
                                'fitBounds' => 'true',
                                'maxZoom' => 25,
                                'tileSize' => 512,
                                'zoomOffset' => -1,
                            ]
                        ))
                );

            $traderAccountForm = $this->createForm(TraderAccountFormType::class, $traderProfile, [
                'map' => $map,
            ]);
            $traderAccountForm->handleRequest($request);

            if ($traderAccountForm->isSubmitted() && $traderAccountForm->isValid()) {
                $location = $traderAccountForm->get('location')->getData();
                $traderProfile->setLatitude($location->getLatitude());
                $traderProfile->setLongitude($location->getLongitude());
                $traderProfile->setServiceRadius($location->getRadiusKm());
                $traderProfile->setAddress($location->getAddress());

                $this->traderProfileRepository->save(entity: $traderProfile, flush: true);

                return $this->redirectToRoute('user_account', [
                    'tab' => 'trader',
                ]);
            }
        }

        // 2) Account form
        $accountForm = $this->createForm(AccountFormType::class, $currentUser);
        $accountForm->handleRequest($request);

        if ($accountForm->isSubmitted() && $accountForm->isValid()) {
            $avatarFile = $accountForm->get('avatar')->getData() ?? null;

            if (! empty($avatarFile)) {
                $optimisedFile = $this->imageOptimizer->getOptimizedAvatarFile($avatarFile);
                $base64Image = $this->imageOptimizer->toBase64($optimisedFile);
                $currentUser->setAvatar($base64Image);
            }

            $this->userRepository->save(entity: $currentUser, flush: true);

            return $this->redirectToRoute('user_account', [
                'tab' => 'account',
            ]);
        }

        // 3) Notification settings
        $settings = $currentUser->getNotificationSettings();

        $userNotificationSettingsTypeForm = $this->createForm(
            UserNotificationSettingsType::class,
            $settings,
            [
                'current_user' => $currentUser,
            ]
        );
        $userNotificationSettingsTypeForm->handleRequest($request);

        if ($userNotificationSettingsTypeForm->isSubmitted() && $userNotificationSettingsTypeForm->isValid()) {
            $this->userRepository->save(entity: $currentUser, flush: true);
            $this->addFlash(FlashEnum::SUCCESS->value, 'flash.notification-settings-saved');

            return $this->redirectToRoute('user_account', [
                'tab' => 'notification',
            ]);
        }

        return $this->render('user/account.html.twig', [
            'tab' => $tab,
            'accountForm' => $accountForm,
            'traderAccountForm' => $traderAccountForm,
            'userNotificationSettingsTypeForm' => $userNotificationSettingsTypeForm,
        ]);
    }
}