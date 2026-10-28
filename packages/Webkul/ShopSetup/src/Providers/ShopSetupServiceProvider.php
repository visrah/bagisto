<?php

namespace Webkul\ShopSetup\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Webkul\ShopSetup\Listeners\CopySectionsToTheme;

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
        }

        Event::listen('appearance.theme.activate.after', [CopySectionsToTheme::class, 'handle']);

        Event::listen('bagisto.admin.dashboard.overall_details.before', function ($viewRenderEventManager) {
            $viewRenderEventManager->addTemplate('shop_setup::admin.checklist');
        });
    }
}
