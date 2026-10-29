<?php

return [
    'acl' => [
        'title' => 'Absa Spark',
    ],
    'menu' => [
        'title' => 'Absa Spark',
    ],
    'settings' => [
        'title' => 'Absa Spark card payments',
        'description' => 'Accept card payments in MUR through the Absa Spark hosted payment page. Customers pay on the secure Absa page, so card details never reach your shop.',
        'enabled' => 'Enable Absa Spark',
        'yes' => 'Yes',
        'no' => 'No',
        'checkout-title' => 'Name shown at checkout',
        'mode' => 'Mode',
        'sandbox' => 'Sandbox (testing)',
        'live' => 'Live',
        'store-id' => 'Store ID',
        'api-key' => 'API key',
        'secret' => 'API secret',
        'saved-secret' => 'A value is saved. Leave blank to keep it.',
        'not-set' => 'Not set yet.',
        'save' => 'Save',
        'updated' => 'Absa Spark settings saved.',
        'mur-only' => 'Absa Spark accepts MUR only. Your shop currency is :currency, so this method stays hidden at checkout.',
        'credentials-required' => 'Enter the store ID, API key and secret before enabling Absa Spark.',
        'webhook-url' => 'Webhook address',
        'webhook-hint' => 'Give this address to Absa so it can confirm payments. It must be reachable over HTTPS.',
    ],
    'messages' => [
        'unavailable' => 'We could not reach the payment page. Your items were not charged. Please try again.',
        'cart-missing' => 'Your cart could not be found. Please check your cart and try again.',
        'cart-invalid' => 'Your cart cannot be paid right now. Please review it and try again.',
        'mur-only' => 'Card payment through Absa is available in MUR only.',
        'confirming' => 'We are confirming your payment. You will receive an email once it is complete.',
        'declined' => 'The payment was declined. Your items were not charged. Please try again.',
        'cancelled' => 'The payment was cancelled. Your items were not charged.',
        'late-payment' => 'Absa took payment (reference :reference) after this order was cancelled. Refund the customer.',
    ],
];
