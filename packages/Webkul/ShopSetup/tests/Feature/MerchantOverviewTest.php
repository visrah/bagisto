<?php

use Webkul\Sales\Models\Invoice;
use Webkul\ShopSetup\Reporting\MerchantOverview;
use Webkul\ShopSetup\Sales\OrderLabels;

use function Pest\Laravel\get;

// ============================================================================
// Merchant Overview
// ============================================================================

it('counts paid invoices for today without treating pending invoices as payment', function () {
    $order = $this->createOrder(['base_grand_total' => 150]);

    Invoice::factory()->create(['order_id' => $order->id, 'state' => 'paid', 'base_grand_total' => 100]);
    Invoice::factory()->create(['order_id' => $order->id, 'state' => 'pending', 'base_grand_total' => 50]);

    $summary = app(MerchantOverview::class)->dashboard();

    expect($summary['sales_today'])->toBe(100.0)
        ->and($summary['recent_orders']->count())->toBeGreaterThan(0);
});

it('keeps payment and fulfilment labels separate', function () {
    $order = $this->createOrder(['base_grand_total' => 100], [['qty_ordered' => 1, 'qty_shipped' => 1]]);
    $labels = app(OrderLabels::class);

    expect($labels->payment($order))->toBe('awaiting-payment')
        ->and($labels->fulfilment($order))->toBe('shipped');

    Invoice::factory()->create(['order_id' => $order->id, 'state' => 'paid', 'base_grand_total' => 100]);

    expect($labels->payment($order->fresh()))->toBe('paid');
});

it('does not call a pending invoice paid', function () {
    $order = $this->createOrder(['base_grand_total' => 100]);
    Invoice::factory()->create(['order_id' => $order->id, 'state' => 'pending', 'base_grand_total' => 100]);

    expect(app(OrderLabels::class)->payment($order))->toBe('awaiting-payment');
});

it('shows the compact overview and order list in simple mode', function () {
    config(['shop_setup.admin_mode' => 'simple']);
    $this->loginAsAdmin();

    get(route('admin.dashboard.index'))->assertOk()->assertSeeText(trans('shop_setup::merchant.sales-today'));
    get(route('admin.sales.orders.index'))->assertOk()
        ->assertSeeText(trans('shop_setup::merchant.payment-status'))
        ->assertSeeText(trans('shop_setup::merchant.fulfilment-status'));
});

it('keeps stock attention limited to managed stock below the threshold', function () {
    $low = $this->createSimpleProduct(['manage_stock' => ['boolean_value' => 1]]);
    $high = $this->createSimpleProduct(['manage_stock' => ['boolean_value' => 1]]);
    $unmanaged = $this->createSimpleProduct(['manage_stock' => ['boolean_value' => 0]]);

    $low->inventories()->update(['qty' => 2]);
    $high->inventories()->update(['qty' => 100]);
    $unmanaged->inventories()->update(['qty' => 0]);

    $ids = app(MerchantOverview::class)->dashboard()['low_stock']->pluck('product_id');

    expect($ids)->toContain($low->id)->not->toContain($high->id, $unmanaged->id);
});

// ============================================================================
// Order Label Edge Cases
// ============================================================================

it('labels partial and full payment and refunds from recorded money only', function () {
    $labels = app(OrderLabels::class);
    $order = $this->createOrder(['base_grand_total' => 100]);

    Invoice::factory()->create(['order_id' => $order->id, 'state' => 'paid', 'base_grand_total' => 40]);

    expect($labels->payment($order->fresh()))->toBe('partially-paid');

    $order->update(['base_grand_total_refunded' => 10]);

    expect($labels->payment($order->fresh()))->toBe('partially-refunded');

    $order->update(['base_grand_total_refunded' => 100]);

    expect($labels->payment($order->fresh()))->toBe('refunded');
});

it('labels partial shipments, preparing orders and terminal states', function (array $items, array $order, string $expected) {
    expect(app(OrderLabels::class)->fulfilment($this->createOrder($order, $items)))->toBe($expected);
})->with([
    'partial' => [[['qty_ordered' => 3, 'qty_shipped' => 1]], [], 'partially-shipped'],
    'nothing shipped' => [[['qty_ordered' => 3, 'qty_shipped' => 0]], [], 'preparing'],
    'cancelled' => [[['qty_ordered' => 3]], ['status' => 'canceled'], 'cancelled'],
    'closed' => [[['qty_ordered' => 3]], ['status' => 'closed'], 'closed'],
    'canceled remainder' => [[['qty_ordered' => 3, 'qty_shipped' => 1, 'qty_canceled' => 2]], [], 'shipped'],
    'virtual item' => [[['qty_ordered' => 1, 'type' => 'virtual']], [], 'not-required'],
    'unregistered type' => [[['qty_ordered' => 1, 'type' => 'vendor_widget']], [], 'preparing'],
]);

it('names the next merchant step from recorded payment and shipment only', function () {
    $labels = app(OrderLabels::class);
    $order = $this->createOrder(['base_grand_total' => 100], [['qty_ordered' => 1]]);

    expect($labels->nextStep($order))->toBe('next-invoice');

    Invoice::factory()->create(['order_id' => $order->id, 'state' => 'paid', 'base_grand_total' => 100]);

    expect($labels->nextStep($order->fresh()))->toBe('next-ship');

    $order->update(['status' => 'completed']);

    expect($labels->nextStep($order->fresh()))->toBeNull();
});

it('shows the customer count on the merchant dashboard', function () {
    expect(app(MerchantOverview::class)->dashboard())->toHaveKey('total_customers');
});

it('filters the order list by recorded payment', function () {
    config(['shop_setup.admin_mode' => 'simple']);
    $this->loginAsAdmin();

    $paid = $this->createOrder(['base_grand_total' => 100]);
    $unpaid = $this->createOrder(['base_grand_total' => 100]);

    Invoice::factory()->create(['order_id' => $paid->id, 'state' => 'paid', 'base_grand_total' => 100]);

    $ids = fn (string $payment) => app(MerchantOverview::class)->orders()->getCollection()->pluck('id')->all();

    request()->merge(['payment' => 'paid']);

    expect($ids('paid'))->toContain($paid->id)->not->toContain($unpaid->id);

    request()->merge(['payment' => 'awaiting']);

    expect($ids('awaiting'))->toContain($unpaid->id)->not->toContain($paid->id);
});
