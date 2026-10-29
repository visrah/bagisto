<?php

return [
    'acl' => [
        'title' => 'Absa Spark',
    ],
    'menu' => [
        'title' => 'Absa Spark',
    ],
    'settings' => [
        'title' => 'Plăți cu cardul Absa Spark',
        'description' => 'Acceptați plăți cu cardul în MUR prin pagina de plată găzduită Absa Spark. Clienții plătesc pe pagina sigură Absa, deci datele cardului nu ajung niciodată în magazinul dvs.',
        'enabled' => 'Activați Absa Spark',
        'yes' => 'Da',
        'no' => 'Nu',
        'checkout-title' => 'Numele afișat la finalizarea comenzii',
        'mode' => 'Mod',
        'sandbox' => 'Sandbox (testare)',
        'live' => 'Live',
        'store-id' => 'ID magazin',
        'api-key' => 'Cheie API',
        'secret' => 'Secret API',
        'saved-secret' => 'O valoare este salvată. Lăsați gol pentru a o păstra.',
        'not-set' => 'Încă nesetat.',
        'save' => 'Salvează',
        'updated' => 'Setările Absa Spark au fost salvate.',
        'mur-only' => 'Absa Spark acceptă doar MUR. Moneda magazinului dvs. este :currency, deci această metodă rămâne ascunsă la finalizarea comenzii.',
        'credentials-required' => 'Introduceți ID-ul magazinului, cheia API și secretul înainte de a activa Absa Spark.',
        'webhook-url' => 'Adresa webhook',
        'webhook-hint' => 'Dați această adresă către Absa pentru a putea confirma plățile. Trebuie să fie accesibilă prin HTTPS.',
    ],
    'messages' => [
        'unavailable' => 'Nu am putut accesa pagina de plată. Nu a fost taxat nimic. Încercați din nou.',
        'cart-missing' => 'Coșul dvs. nu a fost găsit. Verificați coșul și încercați din nou.',
        'cart-invalid' => 'Coșul dvs. nu poate fi plătit acum. Verificați-l și încercați din nou.',
        'mur-only' => 'Plata cu cardul prin Absa este disponibilă doar în MUR.',
        'confirming' => 'Vă confirmăm plata. Veți primi un e-mail după finalizare.',
        'declined' => 'Plata a fost refuzată. Nu a fost taxat nimic. Încercați din nou.',
        'cancelled' => 'Plata a fost anulată. Nu a fost taxat nimic.',
        'late-payment' => 'Absa a încasat o plată (referință :reference) după anularea acestei comenzi. Rambursați clientul.',
    ],
];
