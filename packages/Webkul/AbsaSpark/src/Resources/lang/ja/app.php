<?php

return [
    'acl' => [
        'title' => 'Absa Spark',
    ],
    'menu' => [
        'title' => 'Absa Spark',
    ],
    'settings' => [
        'title' => 'Absa Spark カード決済',
        'description' => 'Absa Spark のホスト型決済ページで MUR のカード決済を受け付けます。お客様は Absa の安全なページで支払うため、カード情報がショップに届くことはありません。',
        'enabled' => 'Absa Spark を有効にする',
        'yes' => 'はい',
        'no' => 'いいえ',
        'checkout-title' => 'チェックアウトに表示する名前',
        'mode' => 'モード',
        'sandbox' => 'サンドボックス（テスト）',
        'live' => '本番',
        'store-id' => 'ストア ID',
        'api-key' => 'API キー',
        'secret' => 'API シークレット',
        'saved-secret' => '値が保存されています。変更しない場合は空欄のままにしてください。',
        'not-set' => '未設定です。',
        'save' => '保存',
        'updated' => 'Absa Spark の設定を保存しました。',
        'mur-only' => 'Absa Spark は MUR のみ対応しています。ショップの通貨は :currency のため、この支払い方法はチェックアウトで非表示になります。',
        'credentials-required' => 'Absa Spark を有効にする前に、ストア ID、API キー、シークレットを入力してください。',
        'webhook-url' => 'Webhook アドレス',
        'webhook-hint' => 'Absa が支払いを確認できるよう、このアドレスを Absa に伝えてください。HTTPS で到達できる必要があります。',
    ],
    'messages' => [
        'unavailable' => '決済ページに接続できませんでした。請求は発生していません。もう一度お試しください。',
        'cart-missing' => 'カートが見つかりません。カートを確認して、もう一度お試しください。',
        'cart-invalid' => '現在、このカートは支払えません。内容を確認して、もう一度お試しください。',
        'mur-only' => 'Absa のカード決済は MUR のみ利用できます。',
        'confirming' => 'お支払いを確認しています。完了するとメールが届きます。',
        'declined' => '支払いは拒否されました。請求は発生していません。もう一度お試しください。',
        'cancelled' => '支払いはキャンセルされました。請求は発生していません。',
        'late-payment' => 'この注文のキャンセル後に、Absa が支払い（参照番号 :reference）を受け付けました。お客様に返金してください。',
    ],
];
