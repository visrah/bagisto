<?php

namespace Webkul\AbsaSpark\Data;

use Webkul\AbsaSpark\Enums\PaymentStatus;
use Webkul\AbsaSpark\Support\Money;

class CheckoutStatus
{
    /**
     * Create the checkout status.
     */
    public function __construct(
        public readonly string $transactionStatus,
        public readonly ?string $gatewayReference,
        public readonly ?string $errorMessage,
        public readonly ?string $checkoutId,
        public readonly ?string $storeId,
        public readonly ?string $transactionType,
        public readonly ?int $approvedAmount,
        public readonly ?string $currency,
        public readonly ?string $merchantTransactionId,
        public readonly array $raw,
    ) {}

    /**
     * Read the checkout details Absa returns.
     */
    public static function fromResponse(array $response): self
    {
        $status = data_get($response, 'transactionStatus');
        $status = is_string($status) ? $status : '';

        $text = fn (string $key): ?string => is_scalar($value = data_get($response, $key)) && $value !== '' ? (string) $value : null;

        return new self(
            transactionStatus: $status,
            gatewayReference: $text('ipgTransactionDetails.ipgTransactionId'),
            errorMessage: $text('transactionFailure.reason'),
            checkoutId: $text('checkoutId'),
            storeId: $text('storeId'),
            transactionType: $text('transactionType'),
            approvedAmount: PaymentStatus::fromGateway($status) === PaymentStatus::Approved
                ? Money::parseCents(data_get($response, 'approvedAmount.total'))
                : null,
            currency: $text('approvedAmount.currency'),
            merchantTransactionId: $text('requestSent.merchantTransactionId'),
            raw: $response,
        );
    }

    /**
     * The payment status this response stands for.
     */
    public function status(): PaymentStatus
    {
        return PaymentStatus::fromGateway($this->transactionStatus);
    }
}
