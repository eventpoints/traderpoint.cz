<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\MembershipCardTransaction;
use App\Entity\Partner;
use App\Repository\PartnerRepository;
use App\Repository\StoreRepository;
use App\Service\PercentResolver\PercentResolverInterface;
use App\Service\ReferenceGenerator;
use App\Service\UserTokenVerifier\Contract\UserTokenVerifierInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

final class MembershipCardTransactionController extends AbstractController
{
    #[Route('/api/partners/{partnerSlug}/transactions', name: 'api_mct_create', methods: ['POST'])]
    public function create(
        string $partnerSlug,
        Request $request,
        PartnerRepository $partnerRepository,
        StoreRepository $stores,
        UserTokenVerifierInterface $userTokens,
        PercentResolverInterface $percentResolver,
        ReferenceGenerator $refGen,
        EntityManagerInterface $em
    ): JsonResponse {
        $data = json_decode($request->getContent(), true) ?? [];
        $userToken = (string) ($data['user_token'] ?? '');
        $storeCode = (string) ($data['store_code'] ?? '');
        $amountMinor = isset($data['order_amount_minor']) ? (int) $data['order_amount_minor'] : null;

        if ($userToken === '' || $storeCode === '') {
            return $this->json([
                'error' => 'user_token and store_code are required',
            ], 400);
        }

        $partner = $partnerRepository->findOneBySlug($partnerSlug);
        if (! $partner instanceof Partner) {
            return $this->json([
                'error' => 'Unknown partner',
            ], 404);
        }

        $store = $stores->findOneBy([
            'code' => $storeCode,
            'partner' => $partner,
        ]);
        if (! $store || ! $store->isActive()) {
            return $this->json([
                'error' => 'Unknown or inactive store',
            ], 404);
        }

        // 1) Verify/resolve the user from the QR token
        //    Your implementation should check exp/nbf/signature (if JWT) or DB row (if opaque),
        //    and return a user + optional jti.
        $verification = $userTokens->verify($userToken); // -> object with getUser(), getJti()
        $user = $verification->getUser();

        // 2) Compute effective % (store override or partner default)
        $percent = $percentResolver->resolve($partner, $store);

        // 3) Create transaction
        $ref = $refGen->next('MCT'); // e.g. MCT-9F3A2
        $txn = new MembershipCardTransaction($user, $partner, $store, $ref, $percent);
        if ($amountMinor !== null) {
            $txn->setOrderAmountMinor($amountMinor);
        }
        if ($verification->getJti()) {
            $txn->setTokenJti($verification->getJti());
        }

        $em->persist($txn);
        $em->flush();

        return $this->json([
            'status' => 'ok',
            'ref' => $txn->getRef(),
            'applied_percent' => $txn->getAppliedPercent(),
            'store' => $store->getName(),
            'created_at' => $txn->getCreatedAt()->toIso8601String(),
        ], 201);
    }
}
