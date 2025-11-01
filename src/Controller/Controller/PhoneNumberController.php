<?php

namespace App\Controller\Controller;

use App\Entity\PhoneNumber;
use App\Entity\User;
use App\Enum\FlashEnum;
use App\Enum\VerificationPurposeEnum;
use App\Form\Type\PhoneNumberType;
use App\Repository\UserRepository;
use App\Service\VerificationService\PhoneVerificationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Contracts\Translation\TranslatorInterface;

class PhoneNumberController extends AbstractController
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly UserRepository $userRepository,
        private readonly PhoneVerificationService $phoneVerificationService
    )
    {
    }

    #[Route(path: '/add/phone-number', name: 'create_phone_number')]
    public function addPhoneNumber(
        Request $request,
        #[CurrentUser]
        User $currentUser,
    ): Response
    {
        $phoneForm = $this->createForm(PhoneNumberType::class);
        $phoneForm->handleRequest($request);

        if ($phoneForm->isSubmitted() && $phoneForm->isValid()) {
            /** @var PhoneNumber $phone */
            $phone = $phoneForm->getData();
            $currentUser->setPhoneNumber($phone);
            $this->userRepository->save($currentUser, true);

            // Send OTP for phone verification
            $this->phoneVerificationService->start(
                $phone,
                VerificationPurposeEnum::ENGAGEMENT_POSTING,
            );

            $this->addFlash(FlashEnum::INFO->value, $this->translator->trans('We sent you a code by SMS.'));
            return $this->redirectToRoute('phone_verification');
        }

        $phoneFormView = $phoneForm->createView();

        return $this->render('phone/create.html.twig', [
            'phoneForm' => $phoneFormView,
        ]);
    }
}