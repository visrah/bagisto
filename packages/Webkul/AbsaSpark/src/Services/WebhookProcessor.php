<?php

namespace Webkul\AbsaSpark\Services;

use Illuminate\Support\Facades\Log;
use Webkul\AbsaSpark\Enums\PaymentStatus;
use Webkul\AbsaSpark\Repositories\AbsaPaymentRepository;

class WebhookProcessor
{
    /**
     * Create the webhook processor.
     */
    public function __construct(
        protected AbsaPaymentRepository $payments,
        protected FiservClient $client,
        protected PaymentSettler $settler,
    ) {}

    /**
     * Settle the payment a webhook names by asking Absa for the real state, never by trusting the payload.
     */
    public function process(string $checkoutId): void
    {
        $payment = $this->payments->findOneByField('checkout_id', $checkoutId);

        if (! $payment) {
            Log::warning('Absa Spark webhook for an unknown checkout.', ['checkout_id' => $checkoutId]);

            return;
        }

        if ($payment->status === PaymentStatus::Approved) {
            return;
        }

        $this->settler->settle($payment, $this->client->getCheckout($checkoutId));
    }
}
