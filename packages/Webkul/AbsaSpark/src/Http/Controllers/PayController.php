<?php

namespace Webkul\AbsaSpark\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Webkul\AbsaSpark\Exceptions\CheckoutException;
use Webkul\AbsaSpark\Services\PaymentStarter;
use Webkul\Checkout\Facades\Cart;

class PayController extends Controller
{
    /**
     * Reserve stock and send the customer to Absa's hosted payment page.
     */
    public function __invoke(PaymentStarter $starter): RedirectResponse
    {
        $cart = Cart::getCart();

        if (! $cart) {
            session()->flash('error', trans('absa_spark::app.messages.cart-missing'));

            return redirect()->route('shop.checkout.cart.index');
        }

        try {
            $payment = $starter->start($cart->id);
        } catch (CheckoutException $exception) {
            session()->flash('error', $exception->getMessage());

            return redirect()->route('shop.checkout.cart.index');
        }

        return redirect()->away($payment->redirection_url);
    }
}
