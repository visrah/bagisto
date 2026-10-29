<?php

namespace Webkul\AbsaSpark\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;
use Webkul\AbsaSpark\Contracts\AbsaPayment;
use Webkul\AbsaSpark\Enums\PaymentStatus;
use Webkul\AbsaSpark\Exceptions\CheckoutException;
use Webkul\AbsaSpark\Repositories\AbsaPaymentRepository;
use Webkul\AbsaSpark\Support\Money;
use Webkul\CartRule\Exceptions\CouponUsageLimitExceededException;
use Webkul\Checkout\Facades\Cart;
use Webkul\Checkout\Repositories\CartRepository;
use Webkul\Sales\Exceptions\InsufficientInventoryException;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Repositories\OrderRepository;
use Webkul\Sales\Transformers\OrderResource;

class PaymentStarter
{
    /**
     * Create the payment starter.
     */
    public function __construct(
        protected OrderRepository $orders,
        protected CartRepository $carts,
        protected AbsaPaymentRepository $payments,
        protected FiservClient $client,
        protected PaymentSettler $settler,
    ) {}

    /**
     * Reserve stock by creating a pending-payment order for the cart, then open the hosted checkout.
     *
     * @throws CheckoutException
     */
    public function start(int $cartId): AbsaPayment
    {
        return Cache::lock('absa-spark.pay.'.$cartId, 30)->block(10, fn () => $this->begin($cartId));
    }

    /**
     * Create the order, the payment and the checkout for a cart that is still active.
     */
    protected function begin(int $cartId): AbsaPayment
    {
        $cart = $this->carts->find($cartId);

        if (! $cart || ! $cart->is_active) {
            return $this->payments->findWhere(['cart_id' => $cartId, 'status' => PaymentStatus::Pending->value])->sortByDesc('id')->first()
                ?? throw new CheckoutException(trans('absa_spark::app.messages.cart-missing'));
        }

        Cart::setCart($cart);
        Cart::collectTotals();

        $cart = Cart::getCart();

        $this->assertPayable($cart);

        $cents = Money::toCents($cart->base_grand_total);
        $order = $this->createOrder($cart);

        try {
            $payment = DB::transaction(function () use ($order, $cart, $cents) {
                $this->orders->updateOrderStatus($order, Order::STATUS_PENDING_PAYMENT);

                return $this->payments->create([
                    'ulid' => (string) Str::ulid(),
                    'order_id' => $order->id,
                    'cart_id' => $cart->id,
                    'status' => PaymentStatus::Pending,
                    'amount' => $cents,
                    'currency' => config('absa-spark.currency'),
                ]);
            });
        } catch (Throwable $exception) {
            $this->orders->cancel($order);

            throw $exception;
        }

        return $this->openCheckout($payment, $cart->id);
    }

    /**
     * Ask Absa for a hosted checkout; if that fails the order is cancelled and its stock released.
     */
    protected function openCheckout(AbsaPayment $payment, int $cartId): AbsaPayment
    {
        try {
            $checkout = $this->client->createCheckout(
                (int) $payment->amount,
                $payment->currency,
                $payment->ulid,
                route('absa_spark.return', $payment->ulid),
                route('absa_spark.return', $payment->ulid),
                route('absa_spark.webhook'),
            );
        } catch (Throwable $exception) {
            report($exception);

            $this->settler->release($payment, PaymentStatus::Declined, 'The checkout could not be created.', null);

            throw new CheckoutException(trans('absa_spark::app.messages.unavailable'));
        }

        $payment->update([
            'checkout_id' => $checkout['checkout_id'],
            'client_request_id' => $checkout['client_request_id'],
            'redirection_url' => $checkout['redirection_url'],
            'raw_request' => $checkout['request'],
            'raw_response' => $checkout['response'],
        ]);

        Cart::deActivateCart();

        session()->put('absa_spark.cart', $cartId);

        return $payment;
    }

    /**
     * Create the order, which checks and reserves stock under row locks.
     */
    protected function createOrder($cart)
    {
        try {
            return $this->orders->create((new OrderResource($cart))->jsonSerialize());
        } catch (InsufficientInventoryException) {
            throw new CheckoutException(trans('shop::app.checkout.cart.inventory-warning'));
        } catch (CouponUsageLimitExceededException) {
            throw new CheckoutException(trans('shop::app.checkout.coupon.usage-limit-exceeded'));
        }
    }

    /**
     * Refuse carts that cannot be paid through Absa.
     */
    protected function assertPayable($cart): void
    {
        if (! $cart || Cart::hasError() || ! Cart::haveMinimumOrderAmount()) {
            throw new CheckoutException(trans('absa_spark::app.messages.cart-invalid'));
        }

        if ($cart->payment?->method !== 'absaspark') {
            throw new CheckoutException(trans('absa_spark::app.messages.cart-invalid'));
        }

        if ($cart->base_currency_code !== config('absa-spark.currency')) {
            throw new CheckoutException(trans('absa_spark::app.messages.mur-only'));
        }
    }
}
