<?php

/**
 * Returns the importmap for this application.
 *
 * - "path" is a path inside the asset mapper system. Use the
 *     "debug:asset-map" command to see the full list of paths.
 *
 * - "entrypoint" (JavaScript only) set to true for any module that will
 *     be used as an "entrypoint" (and passed to the importmap() Twig function).
 *
 * The "importmap:require" command can be used to add new entries to this file.
 */
return [
    'app' => [
        'path' => './assets/app.js',
        'entrypoint' => true,
    ],
    'admin' => [
        'path' => './assets/admin.js',
        'entrypoint' => true,
    ],
    '@symfony/stimulus-bundle' => [
        'path' => './vendor/symfony/stimulus-bundle/assets/dist/loader.js',
    ],
    '@symfony/ux-leaflet-map' => [
        'path' => './vendor/symfony/ux-leaflet-map/assets/dist/map_controller.js',
    ],
    '@symfony/ux-live-component' => [
        'path' => './vendor/symfony/ux-live-component/assets/dist/live_controller.js',
    ],
    '@hotwired/stimulus' => [
        'version' => '3.2.2',
    ],
    '@hotwired/turbo' => [
        'version' => '8.0.20',
    ],
    'bootstrap' => [
        'version' => '5.3.8',
    ],
    '@popperjs/core' => [
        'version' => '2.11.8',
    ],
    'bootstrap/dist/css/bootstrap.min.css' => [
        'version' => '5.3.8',
        'type' => 'css',
    ],
    'stimulus-character-counter' => [
        'version' => '4.2.0',
    ],
    'stimulus-password-visibility' => [
        'version' => '2.1.1',
    ],
    'stimulus-textarea-autogrow' => [
        'version' => '4.1.0',
    ],
    'stimulus-lightbox' => [
        'version' => '3.2.0',
    ],
    'lightgallery' => [
        'version' => '2.9.0',
    ],
    'stimulus-image-grid' => [
        'version' => '1.0.3',
    ],
    'stimulus' => [
        'version' => '3.2.2',
    ],
    '@stimulus-components/read-more' => [
        'version' => '5.0.0',
    ],
    '@stimulus-components/animated-number' => [
        'version' => '5.0.0',
    ],
    '@stimulus-components/clipboard' => [
        'version' => '5.0.0',
    ],
    '@symfony/stimulus-bridge' => [
        'version' => '4.0.1',
    ],
    '@symfony/stimulus-bridge/lazy-controller-loader' => [
        'version' => '4.0.1',
    ],
    'tom-select' => [
        'version' => '2.4.3',
    ],
    'tom-select/dist/css/tom-select.bootstrap5.css' => [
        'version' => '2.4.3',
        'type' => 'css',
    ],
    'lightgallery/css/lightgallery.css' => [
        'version' => '2.9.0',
        'type' => 'css',
    ],
    'leaflet' => [
        'version' => '1.9.4',
    ],
    'leaflet/dist/leaflet.min.css' => [
        'version' => '1.9.4',
        'type' => 'css',
    ],
    'leaflet/dist/leaflet.css' => [
        'version' => '1.9.4',
        'type' => 'css',
    ],
    '@symfony/ux-dropzone/dist/style.min.css' => [
        'version' => '2.31.0',
        'type' => 'css',
    ],
    'tom-select/dist/css/tom-select.default.css' => [
        'version' => '2.4.3',
        'type' => 'css',
    ],
    'tom-select/dist/css/tom-select.bootstrap4.css' => [
        'version' => '2.4.3',
        'type' => 'css',
    ],
    '@stripe/stripe-js' => [
        'version' => '8.6.0',
    ],
    'tailwindcss' => [
        'version' => '4.1.18',
    ],
    'tailwindcss/index.min.css' => [
        'version' => '4.1.18',
        'type' => 'css',
    ],
    'flowbite-datepicker' => [
        'version' => '2.0.0',
    ],
    'flatpickr' => [
        'version' => '4.6.13',
    ],
    'flatpickr/dist/flatpickr.min.css' => [
        'version' => '4.6.13',
        'type' => 'css',
    ],
    '@stimulus/core' => [
        'version' => '2.0.0',
    ],
    '@stimulus/mutation-observers' => [
        'version' => '2.0.0',
    ],
    '@stimulus/multimap' => [
        'version' => '2.0.0',
    ],
    '@orchidjs/sifter' => [
        'version' => '1.1.0',
    ],
    '@orchidjs/unicode-variants' => [
        'version' => '1.1.2',
    ],
    'tom-select/dist/css/tom-select.default.min.css' => [
        'version' => '2.4.3',
        'type' => 'css',
    ],
];
