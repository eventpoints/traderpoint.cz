<?php

declare(strict_types=1);

use App\Entity\Engagement;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->extension('framework', [
        'workflows' => [
            'engagement' => [
                'type' => 'state_machine',
                'audit_trail' => [
                    'enabled' => true,
                ],
                'marking_store' => [
                    'type' => 'method',
                    'property' => 'status',
                ],
                'supports' => [
                    Engagement::class,
                ],
                'initial_marking' => 'UNDER_ADMIN_REVIEW',
                'places' => [
                    'UNDER_ADMIN_REVIEW',
                    'REJECTED',
                    'RECEIVING_QUOTES',
                    'QUOTE_ACCEPTED',
                    'IN_PROGRESS',
                    'ISSUE_RESOLUTION',
                    'WORK_COMPLETED',
                    'AWAITING_REVIEW',
                    'REVIEWED',
                    'CANCELLED',
                ],
                'transitions' => [
                    'approve' => [
                        'from' => 'UNDER_ADMIN_REVIEW',
                        'to' => 'RECEIVING_QUOTES',
                        'guard' => 'is_granted(\'ROLE_ADMIN\')',
                    ],
                    'reject' => [
                        'from' => 'UNDER_ADMIN_REVIEW',
                        'to' => 'REJECTED',
                        'guard' => 'is_granted(\'ROLE_ADMIN\')',
                    ],
                    'accept_quote' => [
                        'from' => 'RECEIVING_QUOTES',
                        'to' => 'QUOTE_ACCEPTED',
                    ],
                    'start_work' => [
                        'from' => 'QUOTE_ACCEPTED',
                        'to' => 'IN_PROGRESS',
                    ],
                    'raise_issue' => [
                        'from' => [
                            'IN_PROGRESS',
                            'WORK_COMPLETED',
                        ],
                        'to' => 'ISSUE_RESOLUTION',
                    ],
                    'resolve_issue_continue' => [
                        'from' => 'ISSUE_RESOLUTION',
                        'to' => 'IN_PROGRESS',
                    ],
                    'resolve_issue_restart' => [
                        'from' => 'ISSUE_RESOLUTION',
                        'to' => 'RECEIVING_QUOTES',
                    ],
                    'complete_work' => [
                        'from' => 'IN_PROGRESS',
                        'to' => 'WORK_COMPLETED',
                    ],
                    'request_review' => [
                        'from' => 'WORK_COMPLETED',
                        'to' => 'AWAITING_REVIEW',
                    ],
                    'submit_review' => [
                        'from' => 'AWAITING_REVIEW',
                        'to' => 'REVIEWED',
                    ],
                    'cancel' => [
                        'from' => [
                            'UNDER_ADMIN_REVIEW',
                            'RECEIVING_QUOTES',
                            'QUOTE_ACCEPTED',
                            'IN_PROGRESS',
                        ],
                        'to' => 'CANCELLED',
                    ],
                ],
            ],
        ],
    ]);
};
