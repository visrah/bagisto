<?php

use Webkul\AbsaSpark\Payment\AbsaSpark;

return [
    'absaspark' => [
        'class' => AbsaSpark::class,
        'code' => 'absaspark',
        'title' => 'Card payment',
        'description' => 'Pay by card on the secure Absa payment page.',
        'active' => false,
        'sort' => 2,
    ],
];
