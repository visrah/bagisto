<?php

use Webkul\AbsaSpark\Enums\PaymentStatus;
use Webkul\AbsaSpark\Support\Money;

// ============================================================================
// Money
// ============================================================================

it('converts a decimal amount into whole cents', function (string|float $amount, int $cents) {
    expect(Money::toCents($amount))->toBe($cents);
})->with([
    'whole rupees' => [199, 19900],
    'one decimal' => [125.5, 12550],
    'two decimals' => ['10.99', 1099],
    'rounds to the cent' => [10.004, 1000],
]);

it('refuses an amount that is not positive or not a number', function (string|float $amount) {
    expect(fn () => Money::toCents($amount))->toThrow(InvalidArgumentException::class);
})->with(['zero' => [0], 'negative' => [-5], 'text' => ['abc']]);

it('converts cents back into the decimal Fiserv expects', function () {
    expect(Money::toDecimal(12550))->toBe(125.5)
        ->and(Money::toDecimal(1))->toBe(0.01);
});

it('reads a gateway amount into cents without float drift', function (mixed $amount, ?int $cents) {
    expect(Money::parseCents($amount))->toBe($cents);
})->with([
    'two decimals' => ['199.00', 19900],
    'one decimal' => ['199.5', 19950],
    'no decimals' => ['7', 700],
    'three decimals' => ['1.234', null],
    'text' => ['abc', null],
    'array' => [[], null],
]);

// ============================================================================
// Payment Status
// ============================================================================

it('maps the gateway status text to a payment status', function (string $text, PaymentStatus $status) {
    expect(PaymentStatus::fromGateway($text))->toBe($status);
})->with([
    ['APPROVED', PaymentStatus::Approved],
    ['approved', PaymentStatus::Approved],
    ['WAITING', PaymentStatus::Pending],
    ['INITIATED', PaymentStatus::Pending],
    ['CANCELLED', PaymentStatus::Cancelled],
    ['DECLINED', PaymentStatus::Declined],
    ['SOMETHING ELSE', PaymentStatus::Declined],
]);
