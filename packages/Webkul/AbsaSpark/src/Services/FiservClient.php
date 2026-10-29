<?php

namespace Webkul\AbsaSpark\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;
use Webkul\AbsaSpark\Data\CheckoutStatus;
use Webkul\AbsaSpark\Support\Money;

class FiservClient
{
    /**
     * Create the Fiserv client.
     */
    public function __construct(protected AbsaSettings $settings) {}

    /**
     * Create a hosted checkout and return the request that was sent, the response and the request id.
     *
     * @return array{checkout_id: string, redirection_url: string, client_request_id: string, request: array, response: array}
     */
    public function createCheckout(int $cents, string $currency, string $reference, string $successUrl, string $failureUrl, string $webhookUrl): array
    {
        $request = [
            'storeId' => $this->settings->storeId(),
            'transactionType' => 'SALE',
            'transactionOrigin' => 'ECOM',
            'transactionAmount' => [
                'total' => Money::toDecimal($cents),
                'currency' => $currency,
            ],
            'merchantTransactionId' => $reference,
            'checkoutSettings' => [
                'locale' => 'en_GB',
                'redirectBackUrls' => [
                    'successUrl' => $successUrl,
                    'failureUrl' => $failureUrl,
                ],
                'webHooksUrl' => $webhookUrl,
            ],
        ];

        $body = json_encode($request, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        $clientRequestId = (string) Str::uuid();

        $response = $this->send($this->signed($clientRequestId, $body)->withBody($body, 'application/json'), 'post', '/checkouts');

        $checkout = data_get($response->json(), 'checkout');
        $checkoutId = data_get($checkout, 'checkoutId');
        $redirectionUrl = data_get($checkout, 'redirectionUrl');

        if (! is_string($checkoutId) || $checkoutId === '' || ! is_string($redirectionUrl) || $redirectionUrl === '') {
            throw new RuntimeException('Absa did not return a checkout.');
        }

        return [
            'checkout_id' => $checkoutId,
            'redirection_url' => $redirectionUrl,
            'client_request_id' => $clientRequestId,
            'request' => $request,
            'response' => (array) $response->json(),
        ];
    }

    /**
     * Fetch the authoritative state of a checkout.
     */
    public function getCheckout(string $checkoutId): CheckoutStatus
    {
        $response = $this->send($this->signed((string) Str::uuid(), ''), 'get', '/checkouts/'.rawurlencode($checkoutId));

        return CheckoutStatus::fromResponse((array) $response->json());
    }

    /**
     * Compute the Message-Signature header value.
     */
    public function signature(string $clientRequestId, string $timestamp, string $body = ''): string
    {
        return base64_encode(hash_hmac(
            'sha256',
            $this->settings->apiKey().$clientRequestId.$timestamp.$body,
            (string) $this->settings->secret(),
            binary: true,
        ));
    }

    /**
     * Build a request carrying the signed Fiserv headers.
     */
    protected function signed(string $clientRequestId, string $body): PendingRequest
    {
        $timestamp = (string) now()->getTimestampMs();

        return Http::baseUrl($this->settings->baseUrl())
            ->acceptJson()
            ->connectTimeout(30)
            ->timeout(60)
            ->withHeaders([
                'Api-Key' => $this->settings->apiKey(),
                'Client-Request-Id' => $clientRequestId,
                'Timestamp' => $timestamp,
                'Message-Signature' => $this->signature($clientRequestId, $timestamp, $body),
            ]);
    }

    /**
     * Send a request, retrying connection failures and server errors.
     */
    protected function send(PendingRequest $request, string $method, string $path): Response
    {
        return $request
            ->retry(3, 300, fn ($exception) => $exception instanceof ConnectionException
                || ($exception instanceof RequestException && $exception->response->serverError()), throw: false)
            ->{$method}($path)
            ->throw();
    }
}
