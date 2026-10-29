<?php

return [
    'acl' => [
        'title' => 'Absa Spark',
    ],
    'menu' => [
        'title' => 'Absa Spark',
    ],
    'settings' => [
        'title' => 'Paiements par carte Absa Spark',
        'description' => 'Acceptez les paiements par carte en MUR via la page de paiement hébergée Absa Spark. Les clients paient sur la page sécurisée d\'Absa, les données de carte n\'arrivent donc jamais dans votre boutique.',
        'enabled' => 'Activer Absa Spark',
        'yes' => 'Oui',
        'no' => 'Non',
        'checkout-title' => 'Nom affiché au paiement',
        'mode' => 'Mode',
        'sandbox' => 'Bac à sable (test)',
        'live' => 'Production',
        'store-id' => 'Identifiant de boutique',
        'api-key' => 'Clé API',
        'secret' => 'Secret API',
        'saved-secret' => 'Une valeur est enregistrée. Laissez vide pour la conserver.',
        'not-set' => 'Pas encore défini.',
        'save' => 'Enregistrer',
        'updated' => 'Paramètres Absa Spark enregistrés.',
        'mur-only' => 'Absa Spark accepte uniquement la MUR. La devise de votre boutique est :currency, ce moyen de paiement reste donc masqué au paiement.',
        'credentials-required' => 'Saisissez l\'identifiant de boutique, la clé API et le secret avant d\'activer Absa Spark.',
        'webhook-url' => 'Adresse du webhook',
        'webhook-hint' => 'Communiquez cette adresse à Absa pour qu\'il confirme les paiements. Elle doit être accessible en HTTPS.',
    ],
    'messages' => [
        'unavailable' => 'Nous n\'avons pas pu joindre la page de paiement. Aucun montant n\'a été débité. Veuillez réessayer.',
        'cart-missing' => 'Votre panier est introuvable. Vérifiez votre panier et réessayez.',
        'cart-invalid' => 'Votre panier ne peut pas être payé pour le moment. Vérifiez-le et réessayez.',
        'mur-only' => 'Le paiement par carte via Absa n\'est disponible qu\'en MUR.',
        'confirming' => 'Nous confirmons votre paiement. Vous recevrez un e-mail une fois terminé.',
        'declined' => 'Le paiement a été refusé. Aucun montant n\'a été débité. Veuillez réessayer.',
        'cancelled' => 'Le paiement a été annulé. Aucun montant n\'a été débité.',
        'late-payment' => 'Absa a encaissé un paiement (référence :reference) après l\'annulation de cette commande. Remboursez le client.',
    ],
];
