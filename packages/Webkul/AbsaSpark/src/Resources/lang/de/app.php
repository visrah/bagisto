<?php

return [
    'acl' => [
        'title' => 'Absa Spark',
    ],
    'menu' => [
        'title' => 'Absa Spark',
    ],
    'settings' => [
        'title' => 'Kartenzahlungen mit Absa Spark',
        'description' => 'Akzeptieren Sie Kartenzahlungen in MUR über die gehostete Zahlungsseite von Absa Spark. Kunden zahlen auf der sicheren Absa-Seite, Kartendaten gelangen also nie in Ihren Shop.',
        'enabled' => 'Absa Spark aktivieren',
        'yes' => 'Ja',
        'no' => 'Nein',
        'checkout-title' => 'An der Kasse angezeigter Name',
        'mode' => 'Modus',
        'sandbox' => 'Sandbox (Test)',
        'live' => 'Live',
        'store-id' => 'Shop-ID',
        'api-key' => 'API-Schlüssel',
        'secret' => 'API-Geheimnis',
        'saved-secret' => 'Ein Wert ist gespeichert. Leer lassen, um ihn beizubehalten.',
        'not-set' => 'Noch nicht festgelegt.',
        'save' => 'Speichern',
        'updated' => 'Absa-Spark-Einstellungen gespeichert.',
        'mur-only' => 'Absa Spark akzeptiert nur MUR. Ihre Shop-Währung ist :currency, daher bleibt diese Zahlungsart an der Kasse verborgen.',
        'credentials-required' => 'Geben Sie Shop-ID, API-Schlüssel und Geheimnis ein, bevor Sie Absa Spark aktivieren.',
        'webhook-url' => 'Webhook-Adresse',
        'webhook-hint' => 'Geben Sie diese Adresse an Absa weiter, damit Zahlungen bestätigt werden können. Sie muss per HTTPS erreichbar sein.',
    ],
    'messages' => [
        'unavailable' => 'Wir konnten die Zahlungsseite nicht erreichen. Ihnen wurde nichts berechnet. Bitte versuchen Sie es erneut.',
        'cart-missing' => 'Ihr Warenkorb wurde nicht gefunden. Bitte prüfen Sie Ihren Warenkorb und versuchen Sie es erneut.',
        'cart-invalid' => 'Ihr Warenkorb kann derzeit nicht bezahlt werden. Bitte prüfen Sie ihn und versuchen Sie es erneut.',
        'mur-only' => 'Kartenzahlung über Absa ist nur in MUR verfügbar.',
        'confirming' => 'Wir bestätigen Ihre Zahlung. Sie erhalten eine E-Mail, sobald sie abgeschlossen ist.',
        'declined' => 'Die Zahlung wurde abgelehnt. Ihnen wurde nichts berechnet. Bitte versuchen Sie es erneut.',
        'cancelled' => 'Die Zahlung wurde abgebrochen. Ihnen wurde nichts berechnet.',
        'late-payment' => 'Absa hat nach der Stornierung dieser Bestellung eine Zahlung eingezogen (Referenz :reference). Erstatten Sie dem Kunden den Betrag.',
    ],
];
