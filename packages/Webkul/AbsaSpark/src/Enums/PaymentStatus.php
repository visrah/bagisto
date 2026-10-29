<?php

namespace Webkul\AbsaSpark\Enums;

enum PaymentStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Declined = 'declined';
    case Cancelled = 'cancelled';
    case Expired = 'expired';

    /**
     * Translate the status text Absa returns into a payment status.
     */
    public static function fromGateway(string $transactionStatus): self
    {
        return match (mb_strtoupper($transactionStatus)) {
            'APPROVED' => self::Approved,
            'INITIATED', 'WAITING', 'PENDING' => self::Pending,
            'CANCELLED', 'CANCELED' => self::Cancelled,
            default => self::Declined,
        };
    }

    /**
     * Whether the payment has reached an outcome that will not change by itself.
     */
    public function isFinal(): bool
    {
        return $this !== self::Pending;
    }
}
