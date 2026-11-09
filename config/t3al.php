<?php

return [
    /*
     * Subdomains for each surface. Leave a value empty to serve that surface
     * on a path instead (useful locally): /app and /admin on the app's host.
     */
    'domains' => [
        'marketing' => env('T3AL_MARKETING_DOMAIN'),
        'panel' => env('T3AL_PANEL_DOMAIN'),
        'admin' => env('T3AL_ADMIN_DOMAIN'),
    ],

    'locales' => ['ar', 'en'],

    'support' => [
        'whatsapp' => env('T3AL_SUPPORT_WHATSAPP'),
        'hours' => '24/7',
    ],
];
