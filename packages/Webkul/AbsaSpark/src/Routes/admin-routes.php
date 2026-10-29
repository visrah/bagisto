<?php

use Illuminate\Support\Facades\Route;
use Webkul\AbsaSpark\Http\Controllers\Admin\SettingsController;
use Webkul\Core\Http\Middleware\NoCacheMiddleware;

Route::middleware(['web', 'admin', NoCacheMiddleware::class])
    ->prefix(config('app.admin_url'))
    ->group(function () {
        Route::get('settings/absa-spark', [SettingsController::class, 'index'])->name('admin.settings.absa_spark.index');

        Route::put('settings/absa-spark', [SettingsController::class, 'update'])->name('admin.settings.absa_spark.update');

        Route::put('settings/absa-spark/toggle', [SettingsController::class, 'toggle'])->name('admin.settings.absa_spark.toggle');
    });
