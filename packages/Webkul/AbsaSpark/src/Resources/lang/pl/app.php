<?php

return [
    'acl' => [
        'title' => 'Absa Spark',
    ],
    'menu' => [
        'title' => 'Absa Spark',
    ],
    'settings' => [
        'title' => 'Płatności kartą Absa Spark',
        'description' => 'Przyjmuj płatności kartą w MUR przez hostowaną stronę płatności Absa Spark. Klienci płacą na bezpiecznej stronie Absa, więc dane karty nigdy nie trafiają do Twojego sklepu.',
        'enabled' => 'Włącz Absa Spark',
        'yes' => 'Tak',
        'no' => 'Nie',
        'checkout-title' => 'Nazwa wyświetlana przy płatności',
        'mode' => 'Tryb',
        'sandbox' => 'Sandbox (testowy)',
        'live' => 'Produkcyjny',
        'store-id' => 'Identyfikator sklepu',
        'api-key' => 'Klucz API',
        'secret' => 'Sekret API',
        'saved-secret' => 'Wartość jest zapisana. Zostaw puste, aby ją zachować.',
        'not-set' => 'Jeszcze nie ustawiono.',
        'save' => 'Zapisz',
        'updated' => 'Ustawienia Absa Spark zapisane.',
        'mur-only' => 'Absa Spark przyjmuje tylko MUR. Walutą Twojego sklepu jest :currency, więc ta metoda pozostaje ukryta przy płatności.',
        'credentials-required' => 'Wpisz identyfikator sklepu, klucz API i sekret przed włączeniem Absa Spark.',
        'webhook-url' => 'Adres webhooka',
        'webhook-hint' => 'Podaj ten adres Absa, aby mogła potwierdzać płatności. Musi być dostępny przez HTTPS.',
    ],
    'messages' => [
        'unavailable' => 'Nie udało się połączyć ze stroną płatności. Nic nie zostało pobrane. Spróbuj ponownie.',
        'cart-missing' => 'Nie znaleziono Twojego koszyka. Sprawdź koszyk i spróbuj ponownie.',
        'cart-invalid' => 'Twojego koszyka nie można teraz opłacić. Sprawdź go i spróbuj ponownie.',
        'mur-only' => 'Płatność kartą przez Absa jest dostępna tylko w MUR.',
        'confirming' => 'Potwierdzamy Twoją płatność. Po jej zakończeniu otrzymasz e-mail.',
        'declined' => 'Płatność została odrzucona. Nic nie zostało pobrane. Spróbuj ponownie.',
        'cancelled' => 'Płatność została anulowana. Nic nie zostało pobrane.',
        'late-payment' => 'Absa pobrała płatność (numer referencyjny :reference) po anulowaniu tego zamówienia. Zwróć pieniądze klientowi.',
    ],
];
