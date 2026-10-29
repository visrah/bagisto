<?php

return [
    'acl' => [
        'title' => 'Absa Spark',
    ],
    'menu' => [
        'title' => 'Absa Spark',
    ],
    'settings' => [
        'title' => 'Pagos con tarjeta de Absa Spark',
        'description' => 'Acepte pagos con tarjeta en MUR mediante la página de pago alojada de Absa Spark. Los clientes pagan en la página segura de Absa, por lo que los datos de la tarjeta nunca llegan a su tienda.',
        'enabled' => 'Activar Absa Spark',
        'yes' => 'Sí',
        'no' => 'No',
        'checkout-title' => 'Nombre mostrado al pagar',
        'mode' => 'Modo',
        'sandbox' => 'Sandbox (pruebas)',
        'live' => 'Producción',
        'store-id' => 'ID de tienda',
        'api-key' => 'Clave API',
        'secret' => 'Secreto API',
        'saved-secret' => 'Hay un valor guardado. Déjelo en blanco para conservarlo.',
        'not-set' => 'Aún no definido.',
        'save' => 'Guardar',
        'updated' => 'Ajustes de Absa Spark guardados.',
        'mur-only' => 'Absa Spark solo acepta MUR. La moneda de su tienda es :currency, por lo que este método permanece oculto al pagar.',
        'credentials-required' => 'Introduzca el ID de tienda, la clave API y el secreto antes de activar Absa Spark.',
        'webhook-url' => 'Dirección del webhook',
        'webhook-hint' => 'Facilite esta dirección a Absa para que pueda confirmar los pagos. Debe ser accesible por HTTPS.',
    ],
    'messages' => [
        'unavailable' => 'No pudimos acceder a la página de pago. No se le ha cobrado nada. Inténtelo de nuevo.',
        'cart-missing' => 'No se encontró su carrito. Revise su carrito e inténtelo de nuevo.',
        'cart-invalid' => 'Su carrito no se puede pagar ahora. Revíselo e inténtelo de nuevo.',
        'mur-only' => 'El pago con tarjeta mediante Absa solo está disponible en MUR.',
        'confirming' => 'Estamos confirmando su pago. Recibirá un correo electrónico cuando se complete.',
        'declined' => 'El pago fue rechazado. No se le ha cobrado nada. Inténtelo de nuevo.',
        'cancelled' => 'El pago fue cancelado. No se le ha cobrado nada.',
        'late-payment' => 'Absa cobró un pago (referencia :reference) después de cancelarse este pedido. Reembolse al cliente.',
    ],
];
