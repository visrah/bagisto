<?php

return [
    'acl' => [
        'title' => 'Absa Spark',
    ],
    'menu' => [
        'title' => 'Absa Spark',
    ],
    'settings' => [
        'title' => 'Absa Spark 银行卡支付',
        'description' => '通过 Absa Spark 托管支付页面接受 MUR 银行卡支付。客户在 Absa 安全页面付款，因此银行卡信息不会进入您的商店。',
        'enabled' => '启用 Absa Spark',
        'yes' => '是',
        'no' => '否',
        'checkout-title' => '结账时显示的名称',
        'mode' => '模式',
        'sandbox' => '沙盒（测试）',
        'live' => '正式',
        'store-id' => '商店 ID',
        'api-key' => 'API 密钥',
        'secret' => 'API 密文',
        'saved-secret' => '已保存一个值。留空则保留。',
        'not-set' => '尚未设置。',
        'save' => '保存',
        'updated' => 'Absa Spark 设置已保存。',
        'mur-only' => 'Absa Spark 仅支持 MUR。您商店的货币是 :currency，因此该支付方式在结账时保持隐藏。',
        'credentials-required' => '启用 Absa Spark 之前，请输入商店 ID、API 密钥和密文。',
        'webhook-url' => 'Webhook 地址',
        'webhook-hint' => '请将此地址提供给 Absa，以便其确认付款。该地址必须可通过 HTTPS 访问。',
    ],
    'messages' => [
        'unavailable' => '无法连接到支付页面。您没有被扣款。请重试。',
        'cart-missing' => '找不到您的购物车。请检查购物车后重试。',
        'cart-invalid' => '您的购物车目前无法支付。请检查后重试。',
        'mur-only' => '通过 Absa 的银行卡支付仅支持 MUR。',
        'confirming' => '我们正在确认您的付款。完成后您将收到电子邮件。',
        'declined' => '付款被拒绝。您没有被扣款。请重试。',
        'cancelled' => '付款已取消。您没有被扣款。',
        'late-payment' => '此订单取消后，Absa 收到了一笔付款（参考号 :reference）。请向客户退款。',
    ],
];
