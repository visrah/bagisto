<?php

namespace Webkul\ShopSetup\Tax;

class CountryRates
{
    /**
     * The usual standard rate of consumption tax per country, to pre-fill the tax setup. The owner confirms or edits it,
     * because registration thresholds, reduced rates and exemptions differ and are theirs to know.
     *
     * @var array<string, array{name: string, rate: float}>
     */
    protected const array PRESETS = [
        'MU' => ['name' => 'VAT', 'rate' => 15.0],
        'GB' => ['name' => 'VAT', 'rate' => 20.0],
        'FR' => ['name' => 'VAT', 'rate' => 20.0],
        'DE' => ['name' => 'VAT', 'rate' => 19.0],
        'IN' => ['name' => 'GST', 'rate' => 18.0],
        'AU' => ['name' => 'GST', 'rate' => 10.0],
        'ZA' => ['name' => 'VAT', 'rate' => 15.0],
        'NZ' => ['name' => 'GST', 'rate' => 15.0],
    ];

    /**
     * Every preset, keyed by country code.
     *
     * @return array<string, array{name: string, rate: float}>
     */
    public function all(): array
    {
        return self::PRESETS;
    }
}
