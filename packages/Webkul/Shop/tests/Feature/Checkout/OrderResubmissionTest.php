<?php

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Webkul\Sales\Models\Order;

// ============================================================================
// Order Resubmission
// ============================================================================

it('serializes order placement for a customer session', function () {
    $route = Route::getRoutes()->getByName('shop.checkout.onepage.orders.store');

    expect($route->locksFor())->toBe(30)
        ->and($route->waitsFor())->toBe(10);
});

it('does not create another order when the completed cart is submitted again', function () {
    Mail::fake();
    $this->loginAsCustomer();
    $product = $this->createSimpleProduct();
    $cartId = $this->prepareCartForCheckout($product);

    $this->assertOrderPlaced($this->postJson(route('shop.checkout.onepage.orders.store')));

    $this->postJson(route('shop.checkout.onepage.orders.store'));

    expect(Order::query()->where('cart_id', $cartId)->count())->toBe(1);
});
