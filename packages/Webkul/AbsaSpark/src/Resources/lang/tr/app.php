<?php

return [
    'acl' => [
        'title' => 'Absa Spark',
    ],
    'menu' => [
        'title' => 'Absa Spark',
    ],
    'settings' => [
        'title' => 'Absa Spark kart ödemeleri',
        'description' => 'Absa Spark barındırılan ödeme sayfası üzerinden MUR cinsinden kart ödemesi kabul edin. Müşteriler güvenli Absa sayfasında ödeme yapar, bu nedenle kart bilgileri mağazanıza hiç ulaşmaz.',
        'enabled' => 'Absa Spark\'ı etkinleştir',
        'yes' => 'Evet',
        'no' => 'Hayır',
        'checkout-title' => 'Ödemede gösterilen ad',
        'mode' => 'Mod',
        'sandbox' => 'Sandbox (test)',
        'live' => 'Canlı',
        'store-id' => 'Mağaza kimliği',
        'api-key' => 'API anahtarı',
        'secret' => 'API gizli anahtarı',
        'saved-secret' => 'Kayıtlı bir değer var. Korumak için boş bırakın.',
        'not-set' => 'Henüz ayarlanmadı.',
        'save' => 'Kaydet',
        'updated' => 'Absa Spark ayarları kaydedildi.',
        'mur-only' => 'Absa Spark yalnızca MUR kabul eder. Mağazanızın para birimi :currency olduğundan bu yöntem ödemede gizli kalır.',
        'credentials-required' => 'Absa Spark\'ı etkinleştirmeden önce mağaza kimliğini, API anahtarını ve gizli anahtarı girin.',
        'webhook-url' => 'Webhook adresi',
        'webhook-hint' => 'Ödemeleri onaylayabilmesi için bu adresi Absa\'ya verin. HTTPS üzerinden erişilebilir olmalıdır.',
    ],
    'messages' => [
        'unavailable' => 'Ödeme sayfasına ulaşamadık. Ücret alınmadı. Lütfen tekrar deneyin.',
        'cart-missing' => 'Sepetiniz bulunamadı. Lütfen sepetinizi kontrol edip tekrar deneyin.',
        'cart-invalid' => 'Sepetiniz şu anda ödenemiyor. Lütfen gözden geçirip tekrar deneyin.',
        'mur-only' => 'Absa ile kart ödemesi yalnızca MUR cinsinden kullanılabilir.',
        'confirming' => 'Ödemenizi doğruluyoruz. Tamamlandığında e-posta alacaksınız.',
        'declined' => 'Ödeme reddedildi. Ücret alınmadı. Lütfen tekrar deneyin.',
        'cancelled' => 'Ödeme iptal edildi. Ücret alınmadı.',
        'late-payment' => 'Absa, bu sipariş iptal edildikten sonra bir ödeme aldı (referans :reference). Müşteriye geri ödeme yapın.',
    ],
];
