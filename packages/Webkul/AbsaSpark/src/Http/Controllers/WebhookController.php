<?php

namespace Webkul\AbsaSpark\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Webkul\AbsaSpark\Services\WebhookProcessor;

class WebhookController extends Controller
{
    /**
     * Accept Absa's notification and settle the named checkout after the response is sent.
     */
    public function __invoke(Request $request): Response
    {
        $checkoutId = $request->input('checkoutId');

        if (is_string($checkoutId) && $checkoutId !== '') {
            defer(fn () => app(WebhookProcessor::class)->process($checkoutId));
        }

        return response('OK');
    }
}
