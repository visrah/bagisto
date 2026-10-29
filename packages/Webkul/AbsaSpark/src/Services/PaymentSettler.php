<?php

namespace Webkul\AbsaSpark\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use UnexpectedValueException;
use Webkul\AbsaSpark\Contracts\AbsaPayment;
use Webkul\AbsaSpark\Data\CheckoutStatus;
use Webkul\AbsaSpark\Enums\PaymentStatus;
use Webkul\AbsaSpark\Support\Money;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Repositories\InvoiceRepository;
use Webkul\Sales\Repositories\OrderCommentRepository;
use Webkul\Sales\Repositories\OrderRepository;
use Webkul\Sales\Repositories\OrderTransactionRepository;

class PaymentSettler
{
    /**
     * Create the payment settler.
     */
    public function __construct(
        protected OrderRepository $orders,
        protected InvoiceRepository $invoices,
        protected OrderTransactionRepository $transactions,
        protected OrderCommentRepository $comments,
    ) {}

    /**
     * Record the gateway's verdict; safe to call again for the same payment.
     */
    public function settle(AbsaPayment $payment, CheckoutStatus $status): PaymentStatus
    {
        return $this->locked($payment, function (AbsaPayment $payment) use ($status): PaymentStatus {
            $new = $status->status();
            $previous = $payment->status;

            if ($previous === PaymentStatus::Approved || $new === PaymentStatus::Pending) {
                return $previous;
            }

            if ($new === PaymentStatus::Approved) {
                $this->approve($payment, $status);

                return PaymentStatus::Approved;
            }

            if ($previous === PaymentStatus::Pending) {
                $this->release($payment, $new, $status->errorMessage, $status->raw);
            }

            return $payment->status;
        });
    }

    /**
     * Give up on a payment that is still pending and release the stock its order reserved.
     */
    public function expire(AbsaPayment $payment): PaymentStatus
    {
        return $this->locked($payment, function (AbsaPayment $payment): PaymentStatus {
            if ($payment->status !== PaymentStatus::Pending) {
                return $payment->status;
            }

            $this->release($payment, PaymentStatus::Expired, null, null);

            return PaymentStatus::Expired;
        });
    }

    /**
     * Mark a payment as not paid and cancel its order, which returns the reserved stock.
     */
    public function release(AbsaPayment $payment, PaymentStatus $status, ?string $error, ?array $raw): void
    {
        DB::transaction(function () use ($payment, $status, $error, $raw) {
            $payment->update([
                'status' => $status,
                'error_message' => $error ? Str::limit($error, 255, '') : null,
                'raw_response' => $raw ?? $payment->raw_response,
            ]);

            $this->orders->cancel($payment->order);
        });

        Event::dispatch('absa_spark.payment.'.$status->value, $payment);
    }

    /**
     * Confirm the approval matches what was requested, then invoice the order, or flag a payment that arrived after cancellation.
     */
    protected function approve(AbsaPayment $payment, CheckoutStatus $status): void
    {
        $this->assertMatches($payment, $status);

        DB::transaction(function () use ($payment, $status) {
            $order = $payment->order;
            $late = in_array($order->status, [Order::STATUS_CANCELED, Order::STATUS_CLOSED], true);

            $payment->update([
                'status' => PaymentStatus::Approved,
                'gateway_reference' => $status->gatewayReference ?? $payment->gateway_reference,
                'error_message' => null,
                'raw_response' => $status->raw,
                'meta' => $late ? array_merge((array) $payment->meta, ['paid_after_cancellation' => true]) : $payment->meta,
            ]);

            $late ? $this->flagLatePayment($payment) : $this->invoice($payment, $status);
        });

        Event::dispatch('absa_spark.payment.approved', $payment);
    }

    /**
     * Throw unless the gateway approved exactly the stored purchase.
     */
    protected function assertMatches(AbsaPayment $payment, CheckoutStatus $status): void
    {
        $request = (array) $payment->raw_request;

        if (
            $status->checkoutId === null
            || $status->checkoutId !== $payment->checkout_id
            || $status->storeId === null
            || $status->storeId !== ($request['storeId'] ?? null)
            || $status->transactionType === null
            || $status->transactionType !== ($request['transactionType'] ?? null)
            || $status->approvedAmount !== (int) $payment->amount
            || $status->currency !== $payment->currency
            || ($status->merchantTransactionId !== null && $status->merchantTransactionId !== $payment->ulid)
        ) {
            throw new UnexpectedValueException('The approval does not match the stored payment.');
        }
    }

    /**
     * Move the order to processing and record a paid invoice and transaction.
     */
    protected function invoice(AbsaPayment $payment, CheckoutStatus $status): void
    {
        $order = $payment->order;

        $this->orders->update(['status' => Order::STATUS_PROCESSING], $order->id);

        if (! $order->canInvoice()) {
            return;
        }

        $data = ['order_id' => $order->id];

        foreach ($order->items as $item) {
            $data['invoice']['items'][$item->id] = $item->qty_to_invoice;
        }

        $invoice = $this->invoices->create($data);

        $this->transactions->create([
            'transaction_id' => $status->gatewayReference ?? $payment->checkout_id,
            'status' => 'success',
            'type' => $order->payment->method,
            'payment_method' => $order->payment->method,
            'order_id' => $order->id,
            'invoice_id' => $invoice->id,
            'amount' => Money::toDecimal((int) $payment->amount),
            'data' => json_encode($status->raw),
        ]);
    }

    /**
     * Leave a note on a cancelled order that Absa took payment for it, so the merchant refunds it.
     */
    protected function flagLatePayment(AbsaPayment $payment): void
    {
        $this->comments->create([
            'order_id' => $payment->order_id,
            'comment' => trans('absa_spark::app.messages.late-payment', ['reference' => $payment->gateway_reference ?? $payment->checkout_id]),
            'customer_notified' => 0,
        ]);
    }

    /**
     * Serialise work on one payment, because the webhook, the customer's return and the reconciler can race.
     */
    protected function locked(AbsaPayment $payment, callable $callback): PaymentStatus
    {
        return Cache::lock('absa-spark.settle.'.$payment->ulid, 30)->block(10, fn () => $callback($payment->refresh()));
    }
}
