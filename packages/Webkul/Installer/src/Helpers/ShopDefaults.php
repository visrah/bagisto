<?php

namespace Webkul\Installer\Helpers;

use Illuminate\Support\Facades\DB;

class ShopDefaults
{
    /**
     * Payment methods that stay off until the owner connects them, because each needs credentials of its own.
     *
     * @var array<int, string>
     */
    protected const array NEEDS_CREDENTIALS = [
        'paypal_smart_button',
        'paypal_standard',
        'stripe',
        'razorpay',
        'payu',
        'phonepe',
        'payglocal',
    ];

    /**
     * Write the first-run configuration: only the offline payment methods are active, and the shop's origin comes from the options.
     *
     * @param  array{country?: ?string, store_name?: ?string, vat_number?: ?string}  $options
     */
    public function apply(array $options = []): void
    {
        $values = [];

        foreach (self::NEEDS_CREDENTIALS as $method) {
            $values["sales.payment_methods.{$method}.active"] = '0';
        }

        $country = strtoupper(trim((string) ($options['country'] ?? '')));

        if (preg_match('/^[A-Z]{2}$/', $country) === 1) {
            $values['sales.shipping.origin.country'] = $country;
            $values['sales.taxes.default_destination_calculation.country'] = $country;
        }

        foreach (['store_name', 'vat_number'] as $field) {
            $value = trim((string) ($options[$field] ?? ''));

            if ($value !== '') {
                $values["sales.shipping.origin.{$field}"] = mb_substr($value, 0, 190);
            }
        }

        foreach ($values as $code => $value) {
            $this->set($code, $value);
        }
    }

    /**
     * Store one configuration value for every channel and locale, replacing an existing one.
     */
    protected function set(string $code, string $value): void
    {
        $row = DB::table('core_config')
            ->where('code', $code)
            ->whereNull('channel_code')
            ->whereNull('locale_code');

        if ($row->exists()) {
            $row->update(['value' => $value, 'updated_at' => now()]);

            return;
        }

        DB::table('core_config')->insert([
            'code' => $code,
            'value' => $value,
            'channel_code' => null,
            'locale_code' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
