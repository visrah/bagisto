<?php

namespace Webkul\ShopSetup\Navigation;

class MerchantNavigation
{
    /**
     * Keep merchant navigation on existing ACL keys and destinations.
     */
    public function items(): array
    {
        $keys = [
            'dashboard',
            'catalog', 'catalog.products', 'catalog.categories',
            'sales', 'sales.orders', 'sales.shipments', 'sales.invoices', 'sales.refunds', 'sales.eu_withdrawals',
            'customers', 'customers.customers', 'customers.reviews', 'customers.gdpr_requests',
            'appearance',
            'configuration',
        ];

        $items = collect(config('menu.admin'))->keyBy('key');

        return collect($keys)->map(function (string $key, int $index) use ($items) {
            $item = $items->get($key);

            if (! $item) {
                return null;
            }

            $item['sort'] = $index;

            return match ($key) {
                'catalog' => array_replace($item, ['name' => 'shop_setup::merchant.products']),
                'catalog.products' => array_replace($item, ['name' => 'shop_setup::merchant.all-products']),
                'sales' => array_replace($item, ['name' => 'shop_setup::merchant.orders']),
                'sales.orders' => array_replace($item, ['name' => 'shop_setup::merchant.all-orders']),
                'customers.customers' => array_replace($item, ['name' => 'shop_setup::merchant.all-customers']),
                'appearance' => array_replace($item, ['name' => 'shop_setup::merchant.store-design', 'route' => 'admin.appearance.merchant.index']),
                'configuration' => array_replace($item, ['name' => 'shop_setup::merchant.settings', 'route' => 'admin.settings.merchant.index']),
                default => $item,
            };
        })->filter()->values()->all();
    }
}
