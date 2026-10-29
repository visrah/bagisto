<?php

namespace Webkul\AbsaSpark\Payment;

use Webkul\AbsaSpark\Services\AbsaSettings;
use Webkul\Payment\Payment\Payment;

class AbsaSpark extends Payment
{
    /**
     * Payment method code.
     *
     * @var string
     */
    protected $code = 'absaspark';

    /**
     * Whether the method is on, has credentials, and the shop trades in the only currency Absa accepts.
     */
    public function isAvailable(): bool
    {
        return (bool) parent::isAvailable()
            && app(AbsaSettings::class)->isConfigured()
            && core()->getBaseCurrencyCode() === config('absa-spark.currency');
    }

    /**
     * Send the customer to the route that reserves stock and opens the hosted checkout.
     */
    public function getRedirectUrl(): string
    {
        return route('absa_spark.pay');
    }
}
