<?php

return [
    'acl' => [
        'title' => 'Absa Spark',
    ],
    'menu' => [
        'title' => 'Absa Spark',
    ],
    'settings' => [
        'title' => 'Pagamentos com cartão Absa Spark',
        'description' => 'Aceite pagamentos com cartão em MUR pela página de pagamento hospedada do Absa Spark. Os clientes pagam na página segura da Absa, então os dados do cartão nunca chegam à sua loja.',
        'enabled' => 'Ativar Absa Spark',
        'yes' => 'Sim',
        'no' => 'Não',
        'checkout-title' => 'Nome exibido no checkout',
        'mode' => 'Modo',
        'sandbox' => 'Sandbox (teste)',
        'live' => 'Produção',
        'store-id' => 'ID da loja',
        'api-key' => 'Chave de API',
        'secret' => 'Segredo da API',
        'saved-secret' => 'Há um valor salvo. Deixe em branco para mantê-lo.',
        'not-set' => 'Ainda não definido.',
        'save' => 'Salvar',
        'updated' => 'Configurações do Absa Spark salvas.',
        'mur-only' => 'O Absa Spark aceita apenas MUR. A moeda da sua loja é :currency, então este método fica oculto no checkout.',
        'credentials-required' => 'Informe o ID da loja, a chave de API e o segredo antes de ativar o Absa Spark.',
        'webhook-url' => 'Endereço do webhook',
        'webhook-hint' => 'Informe este endereço à Absa para que ela possa confirmar os pagamentos. Ele precisa estar acessível por HTTPS.',
    ],
    'messages' => [
        'unavailable' => 'Não foi possível acessar a página de pagamento. Nada foi cobrado. Tente novamente.',
        'cart-missing' => 'Seu carrinho não foi encontrado. Verifique seu carrinho e tente novamente.',
        'cart-invalid' => 'Seu carrinho não pode ser pago agora. Revise-o e tente novamente.',
        'mur-only' => 'O pagamento com cartão pela Absa está disponível apenas em MUR.',
        'confirming' => 'Estamos confirmando seu pagamento. Você receberá um e-mail quando for concluído.',
        'declined' => 'O pagamento foi recusado. Nada foi cobrado. Tente novamente.',
        'cancelled' => 'O pagamento foi cancelado. Nada foi cobrado.',
        'late-payment' => 'A Absa recebeu um pagamento (referência :reference) depois que este pedido foi cancelado. Reembolse o cliente.',
    ],
];
