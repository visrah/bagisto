<?php

return [
    'menu' => [
        'tax' => 'Tax setup',
    ],

    'acl' => [
        'tax' => 'Tax setup',
    ],

    'tax' => [
        'title' => 'Tax setup',
        'info' => 'Choose how your shop charges tax. The choice applies to every product, and you can change it at any time.',
        'not-confirmed' => 'You have not set up tax yet, so no tax is charged. Choose an option below and save it before your first sale.',
        'country' => 'Where is your shop based?',
        'choose-country' => 'Choose a country',
        'name' => 'Tax name',
        'name-hint' => 'Shown to your customers, for example VAT or GST.',
        'rate' => 'Rate (%)',
        'preset-hint' => 'This is the usual standard rate for the country. Check it against your own situation.',
        'by-country-info' => 'You set a rate for each country you sell to. Orders to a country without a rate are charged no tax, unless you set a rate for all other countries below.',
        'by-country-link' => 'Add or change the rate of a country',
        'other-rate' => 'Rate for all other countries (%)',
        'other-rate-hint' => 'Leave empty to charge no tax to countries without a rate.',
        'exempt-hint' => 'To leave one product untaxed, set its tax category to Exempt on the product page. Leaving the category empty uses your standard rate.',
        'included-discount-note' => 'With prices that include tax, an order that uses a discount code records the tax on the price before the discount. Check this with your accountant if you use discount codes.',
        'disclaimer' => 'This is a setting, not tax advice. Rates, registration thresholds and exemptions depend on your country and business.',
        'save' => 'Save tax setup',
        'saved' => 'Tax setup saved.',

        'modes' => [
            'none' => [
                'title' => 'I do not charge tax',
                'info' => 'No tax is added to prices or orders.',
            ],

            'added' => [
                'title' => 'Add tax at checkout',
                'info' => 'Your prices are shown without tax, and tax is added to the order at checkout.',
            ],

            'included' => [
                'title' => 'My prices already include tax',
                'info' => 'The price a customer sees is the price they pay. The tax is shown as part of it.',
            ],

            'by-country' => [
                'title' => 'Different rates by country',
                'info' => 'The tax depends on the country the order is shipped to.',
            ],
        ],
    ],

    'checklist' => [
        'title' => 'Set up your shop',
        'progress' => ':done of :total done',
        'done' => 'Done',

        'items' => [
            'tax' => [
                'title' => 'Choose how you charge tax',
                'hint' => 'Until you do, no tax is added to any order.',
            ],

            'store' => [
                'title' => 'Add your shop address',
                'hint' => 'Customers and your invoices need to know where you ship from.',
            ],

            'shipping' => [
                'title' => 'Review your shipping rates',
                'hint' => 'Flat rate and free shipping are on by default. Set the price that suits you.',
            ],

            'email' => [
                'title' => 'Set up email',
                'hint' => 'Order confirmations are only sent once a mail server is set.',
            ],

            'product' => [
                'title' => 'Add your first product',
                'hint' => 'Your shop shows products as soon as you add them.',
            ],
        ],
    ],
];
