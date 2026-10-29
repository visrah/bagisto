<?php

return [
    'acl' => [
        'title' => 'Absa Spark',
    ],
    'menu' => [
        'title' => 'Absa Spark',
    ],
    'settings' => [
        'title' => 'Pagamenti con carta Absa Spark',
        'description' => 'Accetta pagamenti con carta in MUR tramite la pagina di pagamento ospitata di Absa Spark. I clienti pagano sulla pagina sicura di Absa, quindi i dati della carta non raggiungono mai il tuo negozio.',
        'enabled' => 'Attiva Absa Spark',
        'yes' => 'Sì',
        'no' => 'No',
        'checkout-title' => 'Nome mostrato al pagamento',
        'mode' => 'Modalità',
        'sandbox' => 'Sandbox (test)',
        'live' => 'Produzione',
        'store-id' => 'ID negozio',
        'api-key' => 'Chiave API',
        'secret' => 'Segreto API',
        'saved-secret' => 'Un valore è salvato. Lascia vuoto per mantenerlo.',
        'not-set' => 'Non ancora impostato.',
        'save' => 'Salva',
        'updated' => 'Impostazioni Absa Spark salvate.',
        'mur-only' => 'Absa Spark accetta solo MUR. La valuta del tuo negozio è :currency, quindi questo metodo resta nascosto al pagamento.',
        'credentials-required' => 'Inserisci ID negozio, chiave API e segreto prima di attivare Absa Spark.',
        'webhook-url' => 'Indirizzo webhook',
        'webhook-hint' => 'Fornisci questo indirizzo ad Absa perché possa confermare i pagamenti. Deve essere raggiungibile tramite HTTPS.',
    ],
    'messages' => [
        'unavailable' => 'Non siamo riusciti a raggiungere la pagina di pagamento. Non ti è stato addebitato nulla. Riprova.',
        'cart-missing' => 'Il tuo carrello non è stato trovato. Controlla il carrello e riprova.',
        'cart-invalid' => 'Il tuo carrello non può essere pagato ora. Controllalo e riprova.',
        'mur-only' => 'Il pagamento con carta tramite Absa è disponibile solo in MUR.',
        'confirming' => 'Stiamo confermando il tuo pagamento. Riceverai un\'e-mail al termine.',
        'declined' => 'Il pagamento è stato rifiutato. Non ti è stato addebitato nulla. Riprova.',
        'cancelled' => 'Il pagamento è stato annullato. Non ti è stato addebitato nulla.',
        'late-payment' => 'Absa ha incassato un pagamento (riferimento :reference) dopo l\'annullamento di questo ordine. Rimborsa il cliente.',
    ],
];
