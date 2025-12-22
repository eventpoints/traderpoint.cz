<?php

declare(strict_types=1);

use App\Entity\User;
use App\Enum\RolesEnum;
use App\Enum\UserRoleEnum;
use App\Security\Social\GoogleAuthenticator;
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
                'stateless' => false,
                'form_login' => [
                    'login_path' => 'app_login',
                    'check_path' => 'app_login',
                    'username_parameter' => 'login_form[email]',
                    'password_parameter' => 'login_form[password]',
                    'csrf_parameter' => '_csrf_token',
                    'csrf_token_id' => 'authenticate',
                ],

                'custom_authenticators' => [
                    GoogleAuthenticator::class,
                ],
                'entry_point' => 'form_login',
                'logout' => [
                    'path' => 'app_logout',
                ],
                'remember_me' => [
                    'secret' => '%kernel.secret%',
                    'lifetime' => 604800,
                    'path' => '/',
                    'always_remember_me' => false,
                    'remember_me_parameter' => 'login_form[_remember_me]',
                ],
                'login_throttling' => [
                    'max_attempts' => 5,
                ],
            ],
        ],

        'access_control' => [
            [
                'path' => '^/login',
                'roles' => RolesEnum::PUBLIC_ACCESS->name,
            ],
            [
                'path' => '^/user/set-password',
                'roles' => RolesEnum::PUBLIC_ACCESS->name,
            ],
            [
                'path' => '^/connect',
                'roles' => RolesEnum::PUBLIC_ACCESS->name,
            ],
//            [
//                'path' => '^/easy-admin',
//                'roles' => UserRoleEnum::ROLE_ADMIN->name,
//            ],
            [
                'path' => '^/[^/]+/qr/',
                'roles' => UserRoleEnum::ROLE_ADMIN->name,
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
