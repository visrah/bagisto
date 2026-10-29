<?php

namespace Webkul\ShopSetup\Http\Controllers\Admin;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Config;
use Illuminate\View\View;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Product\Repositories\ProductInventoryRepository;

class MerchantController extends Controller
{
    /**
     * Show settings with direct links to supported configuration.
     */
    public function index(): View
    {
        $links = [
            ['store-information', 'admin.settings.merchant.branding', [], 'configuration'],
            ['payments', 'admin.settings.merchant.payments', [], 'configuration'],
            ['inventory', 'admin.catalog.inventory.index', [], 'catalog.products'],
            ['delivery', 'admin.configuration.index', ['slug' => 'sales', 'slug2' => 'carriers'], 'configuration'],
            ['stock-settings', 'admin.configuration.index', ['slug' => 'catalog', 'slug2' => 'inventory'], 'configuration'],
            ['taxes', 'admin.settings.shop_tax.index', [], 'settings.shop_tax'],
            ['notifications', 'admin.configuration.index', ['slug' => 'emails', 'slug2' => 'general'], 'configuration'],
            ['email-setup', 'admin.configuration.index', ['slug' => 'emails', 'slug2' => 'configure'], 'configuration'],
            ['account-security', 'admin.account.edit', [], null],
        ];

        return view('shop_setup::admin.hub', ['title' => 'settings', 'links' => $links]);
    }

    /**
     * Open the active storefront editor and existing design tools.
     */
    public function design(): View
    {
        $links = [
            ['homepage-content', 'admin.appearance.sections.index', ['code' => core()->getDefaultChannel()->theme], 'appearance.sections'],
            ['storefront-appearance', 'admin.appearance.themes.index', [], 'appearance.themes'],
            ['pages', 'admin.cms.index', [], 'cms'],
            ['logo-branding', 'admin.settings.merchant.branding', [], 'configuration'],
        ];

        return view('shop_setup::admin.hub', ['title' => 'store-design', 'links' => $links]);
    }

    /**
     * List each payment method with its saved enabled state and a link to its setup, without claiming the account is verified.
     */
    public function payments(): View
    {
        $methods = collect(Config::get('payment_methods'))->map(fn (array $method, string $code) => [
            'code' => $code,
            'title' => core()->getConfigData('sales.payment_methods.'.$code.'.title') ?: $code,
            'active' => (bool) core()->getConfigData('sales.payment_methods.'.$code.'.active'),
            'online' => ! in_array($code, ['cashondelivery', 'moneytransfer'], true),
        ])->sortByDesc('active')->values();

        return view('shop_setup::admin.payments', ['methods' => $methods]);
    }

    /**
     * Page products that keep stock at the default stock location, lowest quantity first.
     */
    public function inventory(): View
    {
        $channel = core()->getDefaultChannel();
        $source = $channel->inventory_sources->where('status', 1)->sortBy('priority')->first();

        $stock = $this->paginateStock($channel->id, $source?->id);

        return view('shop_setup::admin.inventory', ['stock' => $stock, 'source' => $source, 'threshold' => (int) config('shop_setup.low_stock_threshold', 5)]);
    }

    /**
     * Read managed-stock inventory rows for one channel and stock location.
     */
    protected function paginateStock(int $channelId, ?int $sourceId): LengthAwarePaginator
    {
        return app(ProductInventoryRepository::class)->resetModel()
            ->where('inventory_source_id', $sourceId)
            ->whereHas('product', fn ($query) => $query->whereNull('parent_id')
                ->whereHas('channels', fn ($query) => $query->where('channels.id', $channelId))
                ->whereHas('attribute_values', fn ($query) => $query->where('boolean_value', 1)
                    ->whereHas('attribute', fn ($query) => $query->where('code', 'manage_stock'))))
            ->with(['product.attribute_values', 'product.attribute_family'])
            ->orderBy('qty')
            ->orderBy('id')
            ->paginate(20);
    }
}
