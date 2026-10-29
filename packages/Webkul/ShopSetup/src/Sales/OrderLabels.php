<?php

namespace Webkul\ShopSetup\Sales;

use Webkul\Sales\Contracts\Order as OrderContract;
use Webkul\Sales\Models\Invoice;
use Webkul\Sales\Models\Order;

class OrderLabels
{
    /**
     * Describe recorded payment without inferring it from the order or shipment state.
     */
    public function payment(OrderContract $order): string
    {
        $paid = (float) $order->invoices->where('state', Invoice::STATUS_PAID)->sum('base_grand_total');
        $refunded = (float) $order->base_grand_total_refunded;

        if ($refunded > 0) {
            return $refunded >= (float) $order->base_grand_total ? 'refunded' : 'partially-refunded';
        }

        if ($order->invoices->contains('state', Invoice::STATUS_PAID)) {
            return $paid >= (float) $order->base_grand_total ? 'paid' : 'partially-paid';
        }

        return 'awaiting-payment';
    }

    /**
     * Describe shipped quantities using each registered product type's shipping capability.
     */
    public function fulfilment(OrderContract $order): string
    {
        if ($order->status === Order::STATUS_CANCELED) {
            return 'cancelled';
        }

        if ($order->status === Order::STATUS_CLOSED) {
            return 'closed';
        }

        $items = $order->items->filter(fn ($item) => ! config('product_types.'.$item->type.'.class') || app(config('product_types.'.$item->type.'.class'))->isStockable());

        if ($items->isEmpty()) {
            return 'not-required';
        }

        $shipped = $items->sum('qty_shipped');
        $remaining = $items->sum(fn ($item) => max(0, $item->qty_ordered - $item->qty_canceled - $item->qty_refunded - $item->qty_shipped));

        if ($shipped > 0) {
            return $remaining > 0 ? 'partially-shipped' : 'shipped';
        }

        return 'preparing';
    }

    /**
     * Name the single next merchant step, or null when nothing is left to do.
     */
    public function nextStep(OrderContract $order): ?string
    {
        if (in_array($order->status, [Order::STATUS_CANCELED, Order::STATUS_CLOSED, Order::STATUS_COMPLETED], true)) {
            return null;
        }

        if ($this->payment($order) === 'awaiting-payment') {
            return 'next-invoice';
        }

        return in_array($this->fulfilment($order), ['preparing', 'partially-shipped'], true) ? 'next-ship' : null;
    }
}
