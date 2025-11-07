<?php

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->parameters()->set('app.cookies', [
        'categories' => [
            'necessary' => [
                'label' => 'cookies.cat.necessary',
                'description' => 'cookies.cat.necessary.desc',
                'toggleable' => false,
            ],
            'preferences' => [
                'label' => 'cookies.cat.preferences',
                'description' => 'cookies.cat.preferences.desc',
                'toggleable' => true,
                'target' => 'pref',
            ],
            'statistics' => [
                'label' => 'cookies.cat.statistics',
                'description' => 'cookies.cat.statistics.desc',
                'toggleable' => true,
                'target' => 'stat',
            ],
            'marketing' => [
                'label' => 'cookies.cat.marketing',
                'description' => 'cookies.cat.marketing.desc',
                'toggleable' => true,
                'target' => 'mkt',
            ],
        ],

        'vendors' => [
            [
                'key' => 'ga4',
                'name' => 'Google Analytics 4',
                'category' => 'statistics',
                'policy_url' => 'https://policies.google.com/privacy',
                'cookies' => ['_ga', '_ga_*', '_gid'],
                'description' => 'cookies.vendor.ga4',
            ],
            [
                'key' => 'gtm',
                'name' => 'Google Tag Manager',
                'category' => 'statistics', // or 'marketing' if you stuff ad tags in there
                'policy_url' => 'https://policies.google.com/privacy',
                'cookies' => [], // GTM itself is mostly a loader; tags set their own cookies
                'description' => 'cookies.vendor.gtm',
            ],
        ],
    ]);

};
