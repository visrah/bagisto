<?php

namespace Webkul\AbsaSpark\Services;

use Illuminate\Support\Facades\Log;
use Throwable;
use Webkul\AbsaSpark\Contracts\AbsaPayment;
use Webkul\AbsaSpark\Enums\PaymentStatus;
use Webkul\AbsaSpark\Repositories\AbsaPaymentRepository;

class Reconciler
{
    /**
     * Create the reconciler.
     */
    public function __construct(
        protected AbsaPaymentRepository $payments,
        protected FiservClient $client,
        protected PaymentSettler $settler,
    ) {}

    /**
     * Re-check pending payments in case a webhook was lost, expire stale ones, and catch late approvals.
     */
    public function run(): int
    {
        $expireBefore = now()->subMinutes((int) config('absa-spark.expire_after'));
        $checkBefore = now()->subMinutes((int) config('absa-spark.reconcile_after'));
        $recoveryDays = (int) config('absa-spark.recovery_days');

        $this->payments->resetModel()
            ->where('status', PaymentStatus::Pending->value)
            ->whereNull('checkout_id')
            ->where('created_at', '<=', $expireBefore)
            ->each(fn (AbsaPayment $payment) => $this->settler->expire($payment));

        $checked = 0;

        $this->payments->resetModel()
            ->whereNotNull('checkout_id')
            ->where(function ($query) use ($checkBefore, $recoveryDays) {
                $query->where(fn ($query) => $query->where('status', PaymentStatus::Pending->value)->where('created_at', '<=', $checkBefore))
                    ->orWhere(fn ($query) => $query->where('status', PaymentStatus::Expired->value)
                        ->where('created_at', '>=', now()->subDays($recoveryDays))
                        ->where('updated_at', '<=', now()->subHour()));
            })
            ->each(function (AbsaPayment $payment) use ($expireBefore, &$checked) {
                try {
                    $status = $this->settler->settle($payment, $this->client->getCheckout($payment->checkout_id));

                    $checked++;

                    if ($status === PaymentStatus::Pending && $payment->created_at <= $expireBefore) {
                        $this->settler->expire($payment);
                    }

                    if ($status === PaymentStatus::Expired) {
                        $payment->touch();
                    }
                } catch (Throwable $exception) {
                    Log::error('Absa Spark reconciliation failed.', ['payment_id' => $payment->id, 'message' => $exception->getMessage()]);
                }
            });

        return $checked;
    }
}
