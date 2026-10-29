<?php

use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Webkul\AbsaSpark\Enums\PaymentStatus;
use Webkul\AbsaSpark\Models\AbsaPayment;
use Webkul\AbsaSpark\Payment\AbsaSpark;
use Webkul\AbsaSpark\Services\AbsaSettings;
use Webkul\AbsaSpark\Services\Reconciler;
use Webkul\AbsaSpark\Services\WebhookProcessor;
use Webkul\Product\Models\Product;
use Webkul\Sales\Models\Invoice;
use Webkul\Sales\Models\Order;

use function Pest\Laravel\get;
use function Pest\Laravel\post;

// ============================================================================
// Starting A Payment
// ============================================================================

it('reserves stock and opens a hosted checkout with the amount sent as decimal currency', function () {
    Mail::fake();
    $payment = $this->startPayment(stock: 5, quantity: 2);
    $order = Order::query()->findOrFail($payment->order_id);

    expect($order->status)->toBe(Order::STATUS_PENDING_PAYMENT)
        ->and($payment->status)->toBe(PaymentStatus::Pending)
        ->and((int) $payment->amount)->toBe((int) round($order->base_grand_total * 100))
        ->and($payment->checkout_id)->toBe('chk_1')
        ->and(Product::query()->findOrFail($order->items->first()->product_id)->ordered_inventories()->sum('qty'))->toBe(2);

    Http::assertSent(fn ($request) => $request->hasHeader('Message-Signature')
        && $request->hasHeader('Api-Key', 'test-key')
        && $request['storeId'] === 'STORE-1'
        && $request['transactionAmount']['total'] === round($payment->amount / 100, 2)
        && $request['transactionAmount']['currency'] === $payment->currency
        && $request['merchantTransactionId'] === $payment->ulid);
});

it('stops before calling Absa when the stock is no longer there', function () {
    $this->enableAbsa();
    $this->fakeCheckoutCreated();
    $product = $this->setProductStock($this->createSimpleProduct(), 5);
    $this->prepareCartForCheckout($product, [], 'free_free', 'absaspark', 3);
    $this->setProductStock($product, 1);

    get(route('absa_spark.pay'))->assertRedirect(route('shop.checkout.cart.index'))->assertSessionHas('error');

    expect(AbsaPayment::query()->count())->toBe(0);

    Http::assertNothingSent();
});

it('cancels the order and returns the stock when Absa cannot create a checkout', function () {
    $this->enableAbsa();
    Http::fake(['*/checkouts' => Http::response(['error' => 'down'], 500)]);
    $product = $this->setProductStock($this->createSimpleProduct(), 5);
    $this->prepareCartForCheckout($product, [], 'free_free', 'absaspark', 2);

    get(route('absa_spark.pay'))->assertRedirect(route('shop.checkout.cart.index'))->assertSessionHas('error');

    $payment = AbsaPayment::query()->firstOrFail();

    expect($payment->status)->toBe(PaymentStatus::Declined)
        ->and(Order::query()->findOrFail($payment->order_id)->status)->toBe(Order::STATUS_CANCELED)
        ->and((int) $product->ordered_inventories()->sum('qty'))->toBe(0);
});

it('does not offer Absa unless it is on, configured and the shop currency matches', function () {
    $this->enableAbsa();
    $method = app(AbsaSpark::class);

    expect($method->isAvailable())->toBeTrue();

    config(['absa-spark.currency' => 'MUR']);

    expect($method->isAvailable())->toBe(core()->getBaseCurrencyCode() === 'MUR');

    config(['absa-spark.currency' => core()->getBaseCurrencyCode()]);
    app(AbsaSettings::class)->save(['active' => '0']);

    expect($method->isAvailable())->toBeFalse();
});

// ============================================================================
// Settling A Payment
// ============================================================================

it('invoices the order once when the webhook reports the payment approved, even if told twice', function () {
    Mail::fake();
    $this->withoutDefer();
    $payment = $this->startPayment();
    $this->fakeCheckoutState($payment);

    post(route('absa_spark.webhook'), ['checkoutId' => 'chk_1'])->assertOk();
    post(route('absa_spark.webhook'), ['checkoutId' => 'chk_1'])->assertOk();

    $order = Order::query()->findOrFail($payment->order_id);

    expect($payment->refresh()->status)->toBe(PaymentStatus::Approved)
        ->and($payment->gateway_reference)->toBe('ref-1')
        ->and($order->status)->toBe(Order::STATUS_PROCESSING)
        ->and(Invoice::query()->where('order_id', $order->id)->count())->toBe(1);
});

it('ignores a webhook for a checkout it does not know', function () {
    $this->withoutDefer();

    post(route('absa_spark.webhook'), ['checkoutId' => 'unknown'])->assertOk();
    post(route('absa_spark.webhook'), [])->assertOk();

    expect(AbsaPayment::query()->count())->toBe(0);
});

it('cancels the order and returns the stock when the customer comes back after a decline', function () {
    $payment = $this->startPayment(stock: 5, quantity: 2);
    $this->fakeCheckoutState($payment, 'DECLINED');

    get(route('absa_spark.return', $payment->ulid))->assertRedirect(route('shop.checkout.cart.index'))->assertSessionHas('error');

    expect($payment->refresh()->status)->toBe(PaymentStatus::Declined)
        ->and(Order::query()->findOrFail($payment->order_id)->status)->toBe(Order::STATUS_CANCELED)
        ->and((int) Product::query()->findOrFail(Order::query()->findOrFail($payment->order_id)->items->first()->product_id)->ordered_inventories()->sum('qty'))->toBe(0);
});

it('lands an approved customer on the success page', function () {
    Mail::fake();
    $payment = $this->startPayment();
    $this->fakeCheckoutState($payment);

    get(route('absa_spark.return', $payment->ulid))
        ->assertRedirect(route('shop.checkout.onepage.success'))
        ->assertSessionHas('order_id', $payment->order_id);
});

it('keeps the order waiting and tells the customer when Absa has not decided yet', function () {
    $payment = $this->startPayment();
    $this->fakeCheckoutState($payment, 'WAITING');

    get(route('absa_spark.return', $payment->ulid))
        ->assertRedirect(route('shop.checkout.onepage.success'))
        ->assertSessionHas('info');

    expect($payment->refresh()->status)->toBe(PaymentStatus::Pending)
        ->and(Order::query()->findOrFail($payment->order_id)->status)->toBe(Order::STATUS_PENDING_PAYMENT);
});

it('rejects an approval whose amount does not match the order', function () {
    $this->withoutDefer();
    $payment = $this->startPayment();
    $this->fakeCheckoutState($payment, 'APPROVED', ['approvedAmount' => ['total' => '0.01']]);

    expect(fn () => app(WebhookProcessor::class)->process('chk_1'))->toThrow(UnexpectedValueException::class);

    expect($payment->refresh()->status)->toBe(PaymentStatus::Pending)
        ->and(Invoice::query()->where('order_id', $payment->order_id)->count())->toBe(0);
});

it('answers 404 for a return link that does not exist', function () {
    get(route('absa_spark.return', 'does-not-exist'))->assertNotFound();
});

// ============================================================================
// Reconciling Payments
// ============================================================================

it('releases the stock held by a payment that was never completed', function () {
    $payment = $this->startPayment(stock: 5, quantity: 2);
    $this->fakeCheckoutState($payment, 'WAITING');
    $payment->forceFill(['created_at' => now()->subHour()])->save();

    app(Reconciler::class)->run();

    expect($payment->refresh()->status)->toBe(PaymentStatus::Expired)
        ->and(Order::query()->findOrFail($payment->order_id)->status)->toBe(Order::STATUS_CANCELED);
});

it('leaves a recent payment alone', function () {
    $payment = $this->startPayment();
    $this->fakeCheckoutState($payment, 'WAITING');

    app(Reconciler::class)->run();

    expect($payment->refresh()->status)->toBe(PaymentStatus::Pending);
});

it('flags a payment Absa took after the order was already cancelled instead of invoicing it', function () {
    $payment = $this->startPayment();
    $this->fakeCheckoutState($payment, 'WAITING');
    $payment->forceFill(['created_at' => now()->subHour()])->save();
    app(Reconciler::class)->run();

    $this->fakeCheckoutState($payment, 'APPROVED');
    $payment->forceFill(['updated_at' => now()->subHours(2)])->save();

    app(Reconciler::class)->run();

    $order = Order::query()->findOrFail($payment->order_id);

    expect($payment->refresh()->status)->toBe(PaymentStatus::Approved)
        ->and($payment->meta['paid_after_cancellation'])->toBeTrue()
        ->and($order->status)->toBe(Order::STATUS_CANCELED)
        ->and(Invoice::query()->where('order_id', $order->id)->count())->toBe(0)
        ->and($order->comments->pluck('comment')->implode(' '))->toContain('ref-1');
});

// ============================================================================
// Guarding The Payment Route
// ============================================================================

it('sends the customer back to the cart when nothing is in it', function () {
    $this->enableAbsa();

    get(route('absa_spark.pay'))->assertRedirect(route('shop.checkout.cart.index'))->assertSessionHas('error');

    expect(AbsaPayment::query()->count())->toBe(0);
});

it('refuses a cart that chose a different payment method', function () {
    $this->enableAbsa();
    $this->fakeCheckoutCreated();
    $product = $this->setProductStock($this->createSimpleProduct(), 5);
    $this->prepareCartForCheckout($product);

    get(route('absa_spark.pay'))->assertRedirect(route('shop.checkout.cart.index'))->assertSessionHas('error');

    expect(AbsaPayment::query()->count())->toBe(0)
        ->and(Order::query()->count())->toBe(0);

    Http::assertNothingSent();
});

it('does not create a second order when the customer opens the payment link again', function () {
    $payment = $this->startPayment();
    $this->fakeCheckoutCreated();

    get(route('absa_spark.pay'))->assertRedirect(route('shop.checkout.cart.index'));

    expect(Order::query()->where('id', $payment->order_id)->count())->toBe(1)
        ->and(AbsaPayment::query()->count())->toBe(1);
});

// ============================================================================
// Repeated Settlement
// ============================================================================

it('cancels the order only once however many times a decline is reported', function () {
    $payment = $this->startPayment();
    $this->fakeCheckoutState($payment, 'DECLINED');

    get(route('absa_spark.return', $payment->ulid));
    get(route('absa_spark.return', $payment->ulid));

    $order = Order::query()->findOrFail($payment->order_id);

    expect($payment->refresh()->status)->toBe(PaymentStatus::Declined)
        ->and($order->status)->toBe(Order::STATUS_CANCELED)
        ->and((int) $order->items->first()->product->ordered_inventories()->sum('qty'))->toBe(0);
});

it('does not turn an approved payment back into a failure if a later check says declined', function () {
    Mail::fake();
    $payment = $this->startPayment();
    $this->fakeCheckoutState($payment);
    get(route('absa_spark.return', $payment->ulid));

    $this->fakeCheckoutState($payment, 'DECLINED');
    get(route('absa_spark.return', $payment->ulid))->assertRedirect(route('shop.checkout.onepage.success'));

    expect($payment->refresh()->status)->toBe(PaymentStatus::Approved)
        ->and(Order::query()->findOrFail($payment->order_id)->status)->toBe(Order::STATUS_PROCESSING);
});

it('treats a gateway outage on return as still pending instead of failing the order', function () {
    $payment = $this->startPayment();
    Http::swap(new Factory);
    Http::fake(['*' => Http::response([], 500)]);

    get(route('absa_spark.return', $payment->ulid))->assertRedirect(route('shop.checkout.onepage.success'));

    expect($payment->refresh()->status)->toBe(PaymentStatus::Pending)
        ->and(Order::query()->findOrFail($payment->order_id)->status)->toBe(Order::STATUS_PENDING_PAYMENT);
});
