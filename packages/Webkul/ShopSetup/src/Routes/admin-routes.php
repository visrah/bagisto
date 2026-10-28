<?php

use Illuminate\Support\Facades\Route;
use Webkul\Core\Http\Middleware\NoCacheMiddleware;
use Webkul\ShopSetup\Http\Controllers\Admin\TaxController;

Route::middleware(['web', 'admin', NoCacheMiddleware::class])
    ->prefix(config('app.admin_url'))
    ->group(function () {
        Route::controller(TaxController::class)->prefix('settings/shop-tax')->group(function () {
            Route::get('', 'index')->name('admin.settings.shop_tax.index');

            Route::put('', 'update')->name('admin.settings.shop_tax.update');
        });
    });
