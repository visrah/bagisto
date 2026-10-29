<?php

use Illuminate\Support\Facades\Route;
use Webkul\AbsaSpark\Http\Controllers\PayController;
use Webkul\AbsaSpark\Http\Controllers\PaymentReturnController;
use Webkul\AbsaSpark\Http\Controllers\WebhookController;

Route::prefix('absa-spark')->group(function () {
    Route::middleware('web')->group(function () {
        Route::get('pay', PayController::class)->name('absa_spark.pay');

        Route::get('return/{ulid}', PaymentReturnController::class)->middleware('throttle:30,1')->name('absa_spark.return');
    });

    Route::post('webhook', WebhookController::class)->middleware('throttle:60,1')->name('absa_spark.webhook');
});
