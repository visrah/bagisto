<?php

use Illuminate\Support\Facades\Route;
use Webkul\Core\Http\Middleware\NoCacheMiddleware;
use Webkul\ShopSetup\Http\Controllers\Admin\MerchantController;
use Webkul\ShopSetup\Http\Controllers\Admin\SimpleProductController;
use Webkul\ShopSetup\Http\Controllers\Admin\StoreController;
use Webkul\ShopSetup\Http\Controllers\Admin\TaxController;

Route::middleware(['web', 'admin', NoCacheMiddleware::class])
    ->prefix(config('app.admin_url'))
    ->group(function () {
        Route::get('catalog/products/new', [SimpleProductController::class, 'create'])->name('admin.catalog.products.simple.create');

        Route::post('catalog/products/simple', [SimpleProductController::class, 'store'])->name('admin.catalog.products.simple.store');

        Route::get('settings/merchant', [MerchantController::class, 'index'])->name('admin.settings.merchant.index');

        Route::get('settings/merchant/store', [StoreController::class, 'edit'])->name('admin.settings.merchant.branding');

        Route::put('settings/merchant/store', [StoreController::class, 'update'])->name('admin.settings.merchant.update');

        Route::get('settings/merchant/payments', [MerchantController::class, 'payments'])->name('admin.settings.merchant.payments');

        Route::get('catalog/inventory', [MerchantController::class, 'inventory'])->name('admin.catalog.inventory.index');

        Route::get('appearance/merchant', [MerchantController::class, 'design'])->name('admin.appearance.merchant.index');

        Route::controller(TaxController::class)->prefix('settings/shop-tax')->group(function () {
            Route::get('', 'index')->name('admin.settings.shop_tax.index');

            Route::put('', 'update')->name('admin.settings.shop_tax.update');
        });
    });
