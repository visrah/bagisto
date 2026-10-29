<?php

namespace Webkul\AbsaSpark\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Throwable;
use Webkul\AbsaSpark\Enums\PaymentStatus;
use Webkul\AbsaSpark\Repositories\AbsaPaymentRepository;
use Webkul\AbsaSpark\Services\FiservClient;
use Webkul\AbsaSpark\Services\PaymentSettler;
use Webkul\Checkout\Facades\Cart;

class PaymentReturnController extends Controller
{
    /**
     * Create the controller.
     */
    public function __construct(
        protected AbsaPaymentRepository $payments,
        protected FiservClient $client,
        protected PaymentSettler $settler,
    ) {}

    /**
     * Land the customer after the hosted page; the redirect itself is never trusted, the gateway is asked.
     */
    public function __invoke(string $ulid): RedirectResponse
    {
        $payment = $this->payments->findOneByField('ulid', $ulid);

        abort_unless($payment, 404);

        $status = $payment->status;

        if ($status !== PaymentStatus::Approved && $payment->checkout_id) {
            try {
                $status = $this->settler->settle($payment, $this->client->getCheckout($payment->checkout_id));
            } catch (Throwable $exception) {
                report($exception);

                $status = $payment->refresh()->status;
            }
        }

        if ($status === PaymentStatus::Approved || $status === PaymentStatus::Pending) {
            session()->flash('order_id', $payment->order_id);

            if ($status === PaymentStatus::Pending) {
                session()->flash('info', trans('absa_spark::app.messages.confirming'));
            }

            return redirect()->route('shop.checkout.onepage.success');
        }

        $this->restoreCart($payment->cart_id);

        session()->flash('error', trans('absa_spark::app.messages.'.($status === PaymentStatus::Cancelled ? 'cancelled' : 'declined')));

        return redirect()->route('shop.checkout.cart.index');
    }

    /**
     * Give the customer their cart back, but only in the browser session that started the payment.
     */
    protected function restoreCart(?int $cartId): void
    {
        if (
            $cartId
            && ! Cart::getCart()
            && (int) session('absa_spark.cart') === $cartId
        ) {
            Cart::activateCart($cartId);
        }
    }
}
