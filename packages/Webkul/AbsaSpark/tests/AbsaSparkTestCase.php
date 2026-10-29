<?php

namespace Webkul\AbsaSpark\Tests;

use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Http;
use Webkul\AbsaSpark\Models\AbsaPayment;
use Webkul\AbsaSpark\Services\AbsaSettings;
use Webkul\Admin\Tests\Concerns\AdminTestBench;
use Webkul\Shop\Tests\ShopTestCase;

class AbsaSparkTestCase extends ShopTestCase
{
    use AdminTestBench;

    /**
     * Switch Absa Spark on with test credentials and charge in the shop's own currency, which is MUR in production.
     */
    public function enableAbsa(): void
    {
        config(['absa-spark.currency' => core()->getBaseCurrencyCode()]);

        app(AbsaSettings::class)->save([
            'active' => '1',
            'title' => 'Card payment',
            'sandbox' => '1',
            'store_id' => 'STORE-1',
            'api_key' => 'test-key',
            'secret' => 'test-secret',
        ]);
    }

    /**
     * Fake Absa creating a checkout.
     */
    public function fakeCheckoutCreated(string $checkoutId = 'chk_1'): void
    {
        Http::fake([
            '*/checkouts' => Http::response(['checkout' => ['checkoutId' => $checkoutId, 'redirectionUrl' => 'https://pay.test/'.$checkoutId]]),
        ]);
    }

    /**
     * Fake Absa reporting the state of a checkout for a payment.
     */
    public function fakeCheckoutState(AbsaPayment $payment, string $status = 'APPROVED', array $overrides = []): void
    {
        Http::swap(new Factory);

        Http::fake([
            '*/checkouts/*' => Http::response(array_replace_recursive([
                'checkoutId' => $payment->checkout_id,
                'storeId' => 'STORE-1',
                'transactionType' => 'SALE',
                'transactionStatus' => $status,
                'approvedAmount' => ['total' => number_format($payment->amount / 100, 2, '.', ''), 'currency' => $payment->currency],
                'requestSent' => ['merchantTransactionId' => $payment->ulid],
                'ipgTransactionDetails' => ['ipgTransactionId' => 'ref-1'],
            ], $overrides)),
        ]);
    }

    /**
     * Prepare a cart paying with Absa and open the hosted checkout, returning the saved payment.
     */
    public function startPayment(int $stock = 5, int $quantity = 2): AbsaPayment
    {
        $this->enableAbsa();
        $this->fakeCheckoutCreated();

        $product = $this->setProductStock($this->createSimpleProduct(), $stock);
        $this->prepareCartForCheckout($product, [], 'free_free', 'absaspark', $quantity);

        $this->get(route('absa_spark.pay'))->assertRedirect('https://pay.test/chk_1');

        return AbsaPayment::query()->latest('id')->firstOrFail();
    }
}
