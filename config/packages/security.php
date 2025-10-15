<?php

declare(strict_types=1);

use App\Entity\User;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->extension('security', [
        'password_hashers' => [
            PasswordAuthenticatedUserInterface::class => 'auto',
        ],
        'providers' => [
            'app_user_provider' => [
                'entity' => [
                    'class' => User::class,
                    'property' => 'email',
                ],
            ],
        ],
        'firewalls' => [
            'dev' => [
                'pattern' => '^/(_(profiler|wdt)|css|images|js)/',
                'security' => false,
            ],
            'main' => [
                'lazy' => true,
                'provider' => 'app_user_provider',
                'form_login' => [
                    'login_path' => 'app_login',
                    'check_path' => 'app_login',
                    'username_parameter' => 'login_form[email]',
                    'password_parameter' => 'login_form[password]',
                    'csrf_parameter' => '_csrf_token',
                    'csrf_token_id' => 'authenticate',
                ],

                'logout' => [
                    'path' => 'app_logout',
                ],

                // ✅ Remember me hooked to your checkbox
                'remember_me' => [
                    'secret' => '%kernel.secret%',
                    'lifetime' => 604800, // 7 days
                    'path' => '/',
                    'always_remember_me' => false,                  // respect checkbox
                    'remember_me_parameter' => 'login_form[_remember_me]',
                ],

                // (Optional) basic throttling
                'login_throttling' => [
                    'max_attempts' => 5,
                ],
            ],
        ],
        'access_control' => [
            // e.g. allow anonymous to login
            [
                'path' => '^/login',
                'roles' => 'PUBLIC_ACCESS',
            ],
        ],
    ]);

    if ($containerConfigurator->env() === 'test') {
        $containerConfigurator->extension('security', [
            'password_hashers' => [
                PasswordAuthenticatedUserInterface::class => [
                    'algorithm' => 'auto',
                    'cost' => 4,
                    'time_cost' => 3,
                    'memory_cost' => 10,
                ],
            ],
        ]);
    }
};
