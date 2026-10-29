<?php

return [
    'acl' => [
        'title' => 'Absa Spark',
    ],
    'menu' => [
        'title' => 'Absa Spark',
    ],
    'settings' => [
        'title' => 'Pembayaran kartu Absa Spark',
        'description' => 'Terima pembayaran kartu dalam MUR melalui halaman pembayaran Absa Spark. Pelanggan membayar di halaman Absa yang aman, jadi data kartu tidak pernah masuk ke toko Anda.',
        'enabled' => 'Aktifkan Absa Spark',
        'yes' => 'Ya',
        'no' => 'Tidak',
        'checkout-title' => 'Nama yang ditampilkan saat checkout',
        'mode' => 'Mode',
        'sandbox' => 'Sandbox (uji coba)',
        'live' => 'Live',
        'store-id' => 'ID toko',
        'api-key' => 'Kunci API',
        'secret' => 'Rahasia API',
        'saved-secret' => 'Nilai sudah tersimpan. Biarkan kosong untuk mempertahankannya.',
        'not-set' => 'Belum diatur.',
        'save' => 'Simpan',
        'updated' => 'Pengaturan Absa Spark disimpan.',
        'mur-only' => 'Absa Spark hanya menerima MUR. Mata uang toko Anda adalah :currency, jadi metode ini tetap disembunyikan saat checkout.',
        'credentials-required' => 'Masukkan ID toko, kunci API, dan rahasia sebelum mengaktifkan Absa Spark.',
        'webhook-url' => 'Alamat webhook',
        'webhook-hint' => 'Berikan alamat ini kepada Absa agar dapat mengonfirmasi pembayaran. Alamat harus dapat diakses melalui HTTPS.',
    ],
    'messages' => [
        'unavailable' => 'Kami tidak dapat menjangkau halaman pembayaran. Anda tidak ditagih. Silakan coba lagi.',
        'cart-missing' => 'Keranjang Anda tidak ditemukan. Periksa keranjang Anda lalu coba lagi.',
        'cart-invalid' => 'Keranjang Anda tidak dapat dibayar saat ini. Periksa lalu coba lagi.',
        'mur-only' => 'Pembayaran kartu melalui Absa hanya tersedia dalam MUR.',
        'confirming' => 'Kami sedang mengonfirmasi pembayaran Anda. Anda akan menerima email setelah selesai.',
        'declined' => 'Pembayaran ditolak. Anda tidak ditagih. Silakan coba lagi.',
        'cancelled' => 'Pembayaran dibatalkan. Anda tidak ditagih.',
        'late-payment' => 'Absa menerima pembayaran (referensi :reference) setelah pesanan ini dibatalkan. Kembalikan dana kepada pelanggan.',
    ],
];
