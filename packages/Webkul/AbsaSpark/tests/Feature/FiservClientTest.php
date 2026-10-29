<?php

use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Webkul\AbsaSpark\Services\AbsaSettings;
use Webkul\AbsaSpark\Services\FiservClient;

// ============================================================================
// Request Signing
// ============================================================================

it('signs a request with an HMAC of key, request id, timestamp and body', function () {
    $this->enableAbsa();

    $expected = base64_encode(hash_hmac('sha256', 'test-key'.'req-1'.'1700000000000'.'{"a":1}', 'test-secret', true));

    expect(app(FiservClient::class)->signature('req-1', '1700000000000', '{"a":1}'))->toBe($expected);
});

it('sends the checkout request with a signature that matches the exact body it sent', function () {
    $this->enableAbsa();
    $this->fakeCheckoutCreated();

    app(FiservClient::class)->createCheckout(19900, 'MUR', 'ref-1', 'https://shop.test/ok', 'https://shop.test/no', 'https://shop.test/hook');

    Http::assertSent(function (Request $request) {
        $expected = base64_encode(hash_hmac(
            'sha256',
            'test-key'.$request->header('Client-Request-Id')[0].$request->header('Timestamp')[0].$request->body(),
            'test-secret',
            true,
        ));

        return $request->method() === 'POST'
            && str_ends_with($request->url(), '/checkouts')
            && $request->header('Message-Signature')[0] === $expected
            && $request->header('Api-Key')[0] === 'test-key'
            && $request['transactionAmount']['total'] == 199.0
            && $request['transactionAmount']['currency'] === 'MUR'
            && $request['transactionType'] === 'SALE'
            && $request['checkoutSettings']['redirectBackUrls'] === ['successUrl' => 'https://shop.test/ok', 'failureUrl' => 'https://shop.test/no']
            && $request['checkoutSettings']['webHooksUrl'] === 'https://shop.test/hook';
    });
});

it('sends a signed status request with an empty body', function () {
    $this->enableAbsa();
    Http::fake(['*/checkouts/*' => Http::response(['checkoutId' => 'chk_9', 'transactionStatus' => 'WAITING'])]);

    $status = app(FiservClient::class)->getCheckout('chk_9');

    expect($status->checkoutId)->toBe('chk_9');

    Http::assertSent(fn (Request $request) => $request->method() === 'GET'
        && str_ends_with($request->url(), '/checkouts/chk_9')
        && $request->header('Message-Signature')[0] === base64_encode(hash_hmac(
            'sha256',
            'test-key'.$request->header('Client-Request-Id')[0].$request->header('Timestamp')[0],
            'test-secret',
            true,
        )));
});

// ============================================================================
// Endpoints
// ============================================================================

it('talks to the sandbox or production endpoint according to the chosen mode', function () {
    $this->enableAbsa();
    $settings = app(AbsaSettings::class);

    expect($settings->baseUrl())->toBe(AbsaSettings::SANDBOX_URL);

    $settings->save(['sandbox' => '0']);

    expect($settings->baseUrl())->toBe(AbsaSettings::LIVE_URL);

    config(['absa-spark.base_url' => 'http://127.0.0.1:9100']);

    expect($settings->baseUrl())->toBe('http://127.0.0.1:9100');
});

// ============================================================================
// Failures
// ============================================================================

it('retries a server error and succeeds on the next attempt', function () {
    $this->enableAbsa();
    Http::fake(['*/checkouts' => Http::sequence()
        ->push(['error' => 'busy'], 503)
        ->push(['checkout' => ['checkoutId' => 'chk_2', 'redirectionUrl' => 'https://pay.test/chk_2']])]);

    $checkout = app(FiservClient::class)->createCheckout(100, 'MUR', 'ref-2', 'https://a.test', 'https://b.test', 'https://c.test');

    expect($checkout['checkout_id'])->toBe('chk_2');

    Http::assertSentCount(2);
});

it('gives up after three failed attempts', function () {
    $this->enableAbsa();
    Http::fake(['*/checkouts' => Http::response(['error' => 'down'], 500)]);

    expect(fn () => app(FiservClient::class)->createCheckout(100, 'MUR', 'ref-3', 'https://a.test', 'https://b.test', 'https://c.test'))
        ->toThrow(RequestException::class);

    Http::assertSentCount(3);
});

it('does not retry a rejected request', function () {
    $this->enableAbsa();
    Http::fake(['*/checkouts' => Http::response(['error' => 'unauthorised'], 401)]);

    expect(fn () => app(FiservClient::class)->createCheckout(100, 'MUR', 'ref-4', 'https://a.test', 'https://b.test', 'https://c.test'))
        ->toThrow(RequestException::class);

    Http::assertSentCount(1);
});

it('refuses a response that carries no checkout', function () {
    $this->enableAbsa();
    Http::fake(['*/checkouts' => Http::response(['checkout' => []])]);

    expect(fn () => app(FiservClient::class)->createCheckout(100, 'MUR', 'ref-5', 'https://a.test', 'https://b.test', 'https://c.test'))
        ->toThrow(RuntimeException::class, 'Absa did not return a checkout.');
});
