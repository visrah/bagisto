<?php

return [
    'acl' => [
        'title' => 'Absa Spark',
    ],
    'menu' => [
        'title' => 'Absa Spark',
    ],
    'settings' => [
        'title' => 'Kaartbetalingen met Absa Spark',
        'description' => 'Accepteer kaartbetalingen in MUR via de gehoste betaalpagina van Absa Spark. Klanten betalen op de beveiligde Absa-pagina, dus kaartgegevens komen nooit in uw winkel.',
        'enabled' => 'Absa Spark inschakelen',
        'yes' => 'Ja',
        'no' => 'Nee',
        'checkout-title' => 'Naam getoond bij het afrekenen',
        'mode' => 'Modus',
        'sandbox' => 'Sandbox (testen)',
        'live' => 'Live',
        'store-id' => 'Winkel-ID',
        'api-key' => 'API-sleutel',
        'secret' => 'API-geheim',
        'saved-secret' => 'Er is een waarde opgeslagen. Laat leeg om die te behouden.',
        'not-set' => 'Nog niet ingesteld.',
        'save' => 'Opslaan',
        'updated' => 'Absa Spark-instellingen opgeslagen.',
        'mur-only' => 'Absa Spark accepteert alleen MUR. De valuta van uw winkel is :currency, dus deze methode blijft verborgen bij het afrekenen.',
        'credentials-required' => 'Voer de winkel-ID, API-sleutel en het geheim in voordat u Absa Spark inschakelt.',
        'webhook-url' => 'Webhook-adres',
        'webhook-hint' => 'Geef dit adres aan Absa zodat betalingen bevestigd kunnen worden. Het moet bereikbaar zijn via HTTPS.',
    ],
    'messages' => [
        'unavailable' => 'We konden de betaalpagina niet bereiken. Er is niets in rekening gebracht. Probeer het opnieuw.',
        'cart-missing' => 'Uw winkelwagen is niet gevonden. Controleer uw winkelwagen en probeer het opnieuw.',
        'cart-invalid' => 'Uw winkelwagen kan nu niet worden betaald. Controleer hem en probeer het opnieuw.',
        'mur-only' => 'Kaartbetaling via Absa is alleen beschikbaar in MUR.',
        'confirming' => 'We bevestigen uw betaling. U ontvangt een e-mail zodra die is voltooid.',
        'declined' => 'De betaling is geweigerd. Er is niets in rekening gebracht. Probeer het opnieuw.',
        'cancelled' => 'De betaling is geannuleerd. Er is niets in rekening gebracht.',
        'late-payment' => 'Absa heeft een betaling ontvangen (referentie :reference) nadat deze bestelling was geannuleerd. Betaal de klant terug.',
    ],
];
