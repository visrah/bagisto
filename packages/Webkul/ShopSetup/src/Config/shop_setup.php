<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Admin Mode
    |--------------------------------------------------------------------------
    |
    | "simple" gives a new shop's owner the Shop owner role and hides the technical
    | configuration below. "full" keeps the unrestricted Administrator role and every
    | configuration section, for support or an experienced owner.
    |
    */
    'admin_mode' => env('SHOP_ADMIN_MODE', 'simple'),

    /*
    |--------------------------------------------------------------------------
    | Hidden Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration sections a single shop does not need, and a few that the platform
    | itself manages (the media storage). A key hides itself and everything beneath it.
    |
    */
    'hidden_configuration' => [
        'file_management',
        'magic_ai',
        'search_engines.elastic',
        'cache_management',
        'about',
        'general.exchange_rates',
        'general.sitemap',
        'catalog.rich_snippets',
        'sales.rma',
    ],
];
