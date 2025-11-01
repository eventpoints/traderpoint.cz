<?php

namespace App\Controller\Controller;

use App\Entity\User;
use App\Enum\FlashEnum;
use App\Enum\VerificationPurposeEnum;
use App\Enum\VerificationTypeEnum;
use App\Form\Form\VerificationCodeType;
use App\Repository\UserRepository;
use App\Service\VerificationService\PhoneVerificationService;
use Carbon\CarbonImmutable;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Contracts\Translation\TranslatorInterface;

class VerificationController extends AbstractController
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly UserRepository $userRepository,
        private readonly PhoneVerificationService $phoneVerificationService
    )
    {
    }

    #[Route(path: '/phone/verification', name: 'phone_verification')]
    public function index(
        Request $request,
        #[CurrentUser]
        User $currentUser,
    ): Response
    {
        $phoneFormView = null;

        // 2) Phone exists but not confirmed → show code form, verify, mark confirmed, redirect
        if ($currentUser->getPhoneNumber() && ! $currentUser->getPhoneNumber()->getConfirmedAt() instanceof \Carbon\CarbonImmutable) {
            $type = VerificationTypeEnum::PHONE;
            $purpose = VerificationPurposeEnum::ENGAGEMENT_POSTING;

            $codeForm = $this->createForm(VerificationCodeType::class, null, [
                'type' => $type,
                'destination' => $currentUser->getPhoneNumber()->getPhoneNumberWithPrefix(),
                'purpose' => $purpose,
            ])->handleRequest($request);

            if ($codeForm->isSubmitted() && $codeForm->isValid()) {
                $code = $codeForm->get('code')->getData();
                $ok = $this->phoneVerificationService->verify(
                    $currentUser->getPhoneNumber(),
                    $purpose,
                    $code
                );

                if ($ok) {
                    $currentUser->getPhoneNumber()->setConfirmedAt(CarbonImmutable::now());
                    $this->userRepository->save($currentUser, true);

                    $this->addFlash(FlashEnum::SUCCESS->value, $this->translator->trans('Phone verified.'));

                    if ($currentUser->isTrader()) {
                        return $this->redirectToRoute('trader_dashboard');
                    }

                    return $this->redirectToRoute('client_dashboard');
                }

                $codeForm->get('code')->addError(new FormError('Invalid or expired code.'));
            }

            $codeFormView = $codeForm->createView();

            return $this->render('verification/index.html.twig', [
                'phoneForm' => $phoneFormView,
                'codeForm' => $codeFormView,
            ]);
        }

        return $this->redirectToRoute('app_login');
    }
}