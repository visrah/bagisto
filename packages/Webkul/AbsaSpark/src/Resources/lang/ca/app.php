<?php

return [
    'acl' => [
        'title' => 'Absa Spark',
    ],
    'menu' => [
        'title' => 'Absa Spark',
    ],
    'settings' => [
        'title' => 'Pagaments amb targeta Absa Spark',
        'description' => 'Accepteu pagaments amb targeta en MUR mitjançant la pàgina de pagament allotjada d\'Absa Spark. Els clients paguen a la pàgina segura d\'Absa, de manera que les dades de la targeta mai arriben a la vostra botiga.',
        'enabled' => 'Activa Absa Spark',
        'yes' => 'Sí',
        'no' => 'No',
        'checkout-title' => 'Nom mostrat en finalitzar la compra',
        'mode' => 'Mode',
        'sandbox' => 'Sandbox (proves)',
        'live' => 'Producció',
        'store-id' => 'ID de la botiga',
        'api-key' => 'Clau API',
        'secret' => 'Secret API',
        'saved-secret' => 'Hi ha un valor desat. Deixeu-lo en blanc per conservar-lo.',
        'not-set' => 'Encara no definit.',
        'save' => 'Desa',
        'updated' => 'S\'ha desat la configuració d\'Absa Spark.',
        'mur-only' => 'Absa Spark només accepta MUR. La moneda de la vostra botiga és :currency, de manera que aquest mètode queda amagat en finalitzar la compra.',
        'credentials-required' => 'Introduïu l\'ID de la botiga, la clau API i el secret abans d\'activar Absa Spark.',
        'webhook-url' => 'Adreça del webhook',
        'webhook-hint' => 'Doneu aquesta adreça a Absa perquè pugui confirmar els pagaments. Ha de ser accessible per HTTPS.',
    ],
    'messages' => [
        'unavailable' => 'No hem pogut accedir a la pàgina de pagament. No s\'ha cobrat res. Torneu-ho a provar.',
        'cart-missing' => 'No s\'ha trobat el vostre cistell. Reviseu el cistell i torneu-ho a provar.',
        'cart-invalid' => 'El vostre cistell no es pot pagar ara. Reviseu-lo i torneu-ho a provar.',
        'mur-only' => 'El pagament amb targeta mitjançant Absa només està disponible en MUR.',
        'confirming' => 'Estem confirmant el vostre pagament. Rebreu un correu quan s\'hagi completat.',
        'declined' => 'El pagament s\'ha rebutjat. No s\'ha cobrat res. Torneu-ho a provar.',
        'cancelled' => 'El pagament s\'ha cancel·lat. No s\'ha cobrat res.',
        'late-payment' => 'Absa ha cobrat un pagament (referència :reference) després de cancel·lar-se aquesta comanda. Reemborseu el client.',
    ],
];
