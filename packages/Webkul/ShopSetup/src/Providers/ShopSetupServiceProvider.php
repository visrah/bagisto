<?php

namespace Webkul\ShopSetup\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Webkul\ShopSetup\Listeners\CopySectionsToTheme;
use Webkul\ShopSetup\Navigation\MerchantNavigation;
use Webkul\ShopSetup\Reporting\MerchantOverview;

class ShopSetupServiceProvider extends ServiceProvider
{
    /**
     * Register package config.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(dirname(__DIR__).'/Config/menu.php', 'menu.admin');

        $this->mergeConfigFrom(dirname(__DIR__).'/Config/acl.php', 'acl');

        $this->mergeConfigFrom(dirname(__DIR__).'/Config/shop_setup.php', 'shop_setup');
    }

    /**
     * Bootstrap the package.
     */
    public function boot(): void
    {
        $this->loadRoutesFrom(dirname(__DIR__).'/Routes/admin-routes.php');

        $this->loadViewsFrom(dirname(__DIR__).'/Resources/views', 'shop_setup');

        $this->loadTranslationsFrom(dirname(__DIR__).'/Resources/lang', 'shop_setup');

        if (config('shop_setup.admin_mode') !== 'full') {
            config(['system_config_hidden' => config('shop_setup.hidden_configuration')]);

            config(['menu.admin' => app(MerchantNavigation::class)->items()]);
        }

        config(['acl' => collect(config('acl'))->map(function (array $item) {
            $route = match ($item['key']) {
                'catalog.products.create' => ['admin.catalog.products.simple.create', 'admin.catalog.products.simple.store'],
                'configuration' => ['admin.settings.merchant.index', 'admin.settings.merchant.branding', 'admin.settings.merchant.update', 'admin.settings.merchant.payments'],
                'catalog.products' => ['admin.catalog.inventory.index'],
                'appearance' => ['admin.appearance.merchant.index'],
                default => [],
            };

            if ($route) {
                $item['route'] = array_merge((array) $item['route'], $route);
            }

            return $item;
        })->all()]);

        Event::listen('appearance.theme.activate.after', [CopySectionsToTheme::class, 'handle']);

        Event::listen('bagisto.shop.products.short_description.after', function ($manager) {
            $manager->addTemplate('shop_setup::shop.delivery-note');
        });

        Event::listen('bagisto.admin.sales.order.title.after', function ($manager) {
            if (config('shop_setup.admin_mode') !== 'full') {
                $manager->addTemplate('shop_setup::admin.orders.summary');
            }
        });

        View::composer('admin::dashboard.index', function ($view) {
            if (config('shop_setup.admin_mode') !== 'full') {
                $view->setPath(dirname(__DIR__).'/Resources/views/admin/dashboard.blade.php');
                $view->with('summary', app(MerchantOverview::class)->dashboard());
            }
        });

        View::composer('admin::sales.orders.index', function ($view) {
            if (config('shop_setup.admin_mode') !== 'full') {
                $view->setPath(dirname(__DIR__).'/Resources/views/admin/orders/index.blade.php');
                $view->with('orders', app(MerchantOverview::class)->orders());
            }
        });

        Event::listen('bagisto.admin.dashboard.overall_details.before', function ($viewRenderEventManager) {
            $viewRenderEventManager->addTemplate('shop_setup::admin.checklist');
        });
    }
}
