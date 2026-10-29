<?php

namespace Webkul\AbsaSpark\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;
use Webkul\AbsaSpark\Console\ReconcileCommand;

class AbsaSparkServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(dirname(__DIR__).'/Config/payment-methods.php', 'payment_methods');

        $this->mergeConfigFrom(dirname(__DIR__).'/Config/absa-spark.php', 'absa-spark');

        $this->mergeConfigFrom(dirname(__DIR__).'/Config/acl.php', 'acl');

        $this->mergeConfigFrom(dirname(__DIR__).'/Config/menu.php', 'menu.admin');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(dirname(__DIR__).'/Database/Migrations');

        $this->loadRoutesFrom(dirname(__DIR__).'/Routes/shop-routes.php');

        $this->loadRoutesFrom(dirname(__DIR__).'/Routes/admin-routes.php');

        $this->loadViewsFrom(dirname(__DIR__).'/Resources/views', 'absa_spark');

        $this->loadTranslationsFrom(dirname(__DIR__).'/Resources/lang', 'absa_spark');

        if ($this->app->runningInConsole()) {
            $this->commands([ReconcileCommand::class]);
        }

        $this->callAfterResolving(Schedule::class, fn (Schedule $schedule) => $schedule->command('absa-spark:reconcile')->everyMinute()->withoutOverlapping());
    }
}
