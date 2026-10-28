<?php

namespace Webkul\ShopSetup\Tax;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Webkul\ShopSetup\Support\ConfigValues;
use Webkul\Tax\Repositories\TaxCategoryRepository;
use Webkul\Tax\Repositories\TaxRateRepository;

class TaxSetup
{
    /**
     * The shop does not charge tax.
     */
    public const string MODE_NONE = 'none';

    /**
     * Tax is added on top of the prices at checkout.
     */
    public const string MODE_ADDED = 'added';

    /**
     * The prices already include tax.
     */
    public const string MODE_INCLUDED = 'included';

    /**
     * The rate depends on the country the order ships to.
     */
    public const string MODE_BY_COUNTRY = 'by_country';

    /**
     * Code of the tax category every product falls back to.
     */
    protected const string STANDARD_CATEGORY = 'standard';

    /**
     * Code of the tax category an owner picks on a product to leave it untaxed.
     */
    protected const string EXEMPT_CATEGORY = 'exempt';

    /**
     * Create a new tax setup.
     */
    public function __construct(
        protected ConfigValues $config,
        protected TaxCategoryRepository $taxCategories,
        protected TaxRateRepository $taxRates,
    ) {}

    /**
     * Every way of charging tax the owner can choose.
     *
     * @return array<int, string>
     */
    public static function modes(): array
    {
        return [self::MODE_NONE, self::MODE_ADDED, self::MODE_INCLUDED, self::MODE_BY_COUNTRY];
    }

    /**
     * The choice the shop is running on now, and whether the owner has ever confirmed it.
     *
     * @return array{mode: string, confirmed: bool, country: ?string, name: ?string, rate: ?float, other_rate: ?float}
     */
    public function current(): array
    {
        $local = $this->managedRate('shop_setup.tax.local_rate_id');

        $other = $this->managedRate('shop_setup.tax.other_rate_id');

        return [
            'mode' => $this->config->get('shop_setup.tax.mode') ?? self::MODE_NONE,
            'confirmed' => $this->config->get('shop_setup.tax.confirmed') === '1',
            'country' => $this->config->get('sales.shipping.origin.country'),
            'name' => $this->config->get('shop_setup.tax.name'),
            'rate' => $local ? (float) $local->tax_rate : null,
            'other_rate' => $other ? (float) $other->tax_rate : null,
        ];
    }

    /**
     * Turn the owner's choice into tax categories, rates and settings, and confirm it.
     */
    public function apply(string $mode, ?string $country = null, ?string $name = null, ?float $rate = null, ?float $otherRate = null): void
    {
        DB::transaction(function () use ($mode, $country, $name, $rate, $otherRate) {
            if ($mode === self::MODE_NONE) {
                $this->applyNone();
            } else {
                $this->applyTaxed($mode, strtoupper((string) $country), trim((string) $name), (float) $rate, $otherRate);
            }

            $this->config->set('shop_setup.tax.mode', $mode);

            $this->config->set('shop_setup.tax.confirmed', '1');
        });

        Event::dispatch('core.configuration.save.after');
    }

    /**
     * Stop charging tax, keeping the categories and rates in case the owner starts again.
     */
    protected function applyNone(): void
    {
        $this->setMany([
            'sales.taxes.categories.product' => '0',
            'sales.taxes.categories.shipping' => '0',
            'sales.taxes.calculation.product_prices' => 'excluding_tax',
            'sales.taxes.calculation.shipping_prices' => 'excluding_tax',
            ...$this->display('excluding_tax'),
            'sales.taxes.shopping_cart.show_tax_breakdown' => '0',
            'sales.taxes.sales.show_tax_breakdown' => '0',
        ]);
    }

    /**
     * Charge tax: one local rate for a single country, or one rate per country with an optional rate for the rest.
     */
    protected function applyTaxed(string $mode, string $country, string $name, float $rate, ?float $otherRate): void
    {
        $standard = $this->category(self::STANDARD_CATEGORY, 'Standard tax');

        $exempt = $this->category(self::EXEMPT_CATEGORY, 'Exempt (0%)');

        $exempt->tax_rates()->sync([$this->upsertRate('shop_setup.tax.exempt_rate_id', 'Exempt 0%', '*', 0.0)->id]);

        $rateIds = $mode === self::MODE_BY_COUNTRY ? $standard->tax_rates()->pluck('tax_rates.id')->all() : [];

        if ($mode !== self::MODE_BY_COUNTRY) {
            $this->config->set('shop_setup.tax.name', $name);

            $rateIds[] = $this->upsertRate('shop_setup.tax.local_rate_id', "{$name} {$rate}% ({$country})", $country, $rate)->id;
        }

        if ($mode === self::MODE_BY_COUNTRY && $otherRate !== null && $otherRate > 0) {
            $rateIds[] = $this->upsertRate('shop_setup.tax.other_rate_id', "Other countries {$otherRate}%", '*', $otherRate)->id;
        } else {
            $rateIds = array_diff($rateIds, [$this->dropRate('shop_setup.tax.other_rate_id')]);
        }

        $standard->tax_rates()->sync(array_values(array_unique($rateIds)));

        $prices = $mode === self::MODE_INCLUDED ? 'including_tax' : 'excluding_tax';

        $this->setMany([
            'sales.taxes.categories.product' => (string) $standard->id,
            'sales.taxes.categories.shipping' => (string) $standard->id,
            'sales.taxes.calculation.product_prices' => $prices,
            'sales.taxes.calculation.shipping_prices' => $prices,
            'sales.taxes.calculation.based_on' => $mode === self::MODE_BY_COUNTRY ? 'shipping_address' : 'shipping_origin',
            'sales.taxes.calculation.apply_tax_on' => 'after_discount',
            'sales.taxes.default_destination_calculation.country' => $country,
            'sales.shipping.origin.country' => $country,
            ...$this->display($prices),
            'sales.taxes.shopping_cart.show_tax_breakdown' => '1',
            'sales.taxes.sales.show_tax_breakdown' => '1',
        ]);
    }

    /**
     * The display settings for the cart and for orders, invoices and emails, all showing prices the same way.
     *
     * @return array<string, string>
     */
    protected function display(string $prices): array
    {
        $values = [];

        foreach (['shopping_cart', 'sales'] as $area) {
            foreach (['display_prices', 'display_subtotal', 'display_shipping_amount'] as $field) {
                $values["sales.taxes.{$area}.{$field}"] = $prices;
            }
        }

        return $values;
    }

    /**
     * Store several configuration values.
     *
     * @param  array<string, string>  $values
     */
    protected function setMany(array $values): void
    {
        foreach ($values as $code => $value) {
            $this->config->set($code, $value);
        }
    }

    /**
     * The tax category with the code, created when the shop has none yet.
     */
    protected function category(string $code, string $name)
    {
        return $this->taxCategories->findOneByField('code', $code)
            ?? $this->taxCategories->create(['code' => $code, 'name' => $name, 'description' => $name]);
    }

    /**
     * The rate this setup manages under the setting, or null when it has none.
     */
    protected function managedRate(string $setting)
    {
        $id = $this->config->get($setting);

        return $id === null ? null : $this->taxRates->findOneByField('id', $id);
    }

    /**
     * Create the rate this setup manages under the setting, or update it in place so changing the rate never leaves a stale copy.
     */
    protected function upsertRate(string $setting, string $identifier, string $country, float $rate)
    {
        $existing = $this->managedRate($setting);

        $taken = $this->taxRates->findOneByField('identifier', $identifier);

        $attributes = [
            'identifier' => $taken && $taken->id !== $existing?->id ? "{$identifier} [shop setup]" : $identifier,
            'is_zip' => 0,
            'zip_code' => '*',
            'zip_from' => null,
            'zip_to' => null,
            'state' => '*',
            'country' => $country,
            'tax_rate' => $rate,
        ];

        if ($existing) {
            return $this->taxRates->update($attributes, $existing->id);
        }

        $created = $this->taxRates->create($attributes);

        $this->config->set($setting, (string) $created->id);

        return $created;
    }

    /**
     * Delete the rate this setup manages under the setting, returning the id it had, or 0 when there was none.
     */
    protected function dropRate(string $setting): int
    {
        $rate = $this->managedRate($setting);

        if (! $rate) {
            return 0;
        }

        $this->taxRates->delete($rate->id);

        $this->config->set($setting, '');

        return (int) $rate->id;
    }
}
