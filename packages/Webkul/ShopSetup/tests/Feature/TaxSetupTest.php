<?php

use Illuminate\Support\Facades\Event;
use Webkul\ShopSetup\Tax\TaxSetup;
use Webkul\Tax\Facades\Tax;
use Webkul\Tax\Models\TaxCategory;
use Webkul\Tax\Models\TaxRate;

function taxSetup(): TaxSetup
{
    return app(TaxSetup::class);
}

function setting(string $code): mixed
{
    return core()->getConfigData($code);
}

function standardCategory(): TaxCategory
{
    return TaxCategory::query()->where('code', 'standard')->firstOrFail();
}

/**
 * The rate the standard category applies to an address in a country, or null when none applies.
 */
function standardRateFor(string $country): ?TaxRate
{
    $applied = null;

    Tax::isTaxApplicableInCurrentAddress(standardCategory(), (object) ['country' => $country, 'state' => '', 'postcode' => ''], function ($rate) use (&$applied) {
        $applied = $rate;
    });

    return $applied;
}

// ============================================================================
// Not Charging Tax
// ============================================================================

it('should charge no tax and confirm the choice when the shop does not charge tax', function () {
    taxSetup()->apply(TaxSetup::MODE_NONE);

    expect((string) setting('sales.taxes.categories.product'))->toBe('0')
        ->and((string) setting('sales.taxes.categories.shipping'))->toBe('0')
        ->and(taxSetup()->current())->toMatchArray(['mode' => 'none', 'confirmed' => true]);
});

it('should report an unconfirmed choice until the owner saves one', function () {
    expect(taxSetup()->current())->toMatchArray(['mode' => 'none', 'confirmed' => false]);
});

// ============================================================================
// Adding Tax At Checkout
// ============================================================================

it('should add the rate of the shop country on top of the prices', function () {
    taxSetup()->apply(TaxSetup::MODE_ADDED, 'mu', 'VAT', 15.0);

    expect(standardRateFor('MU'))->tax_rate->toEqual(15)
        ->and(standardCategory()->tax_rates()->count())->toBe(1)
        ->and(setting('sales.taxes.calculation.product_prices'))->toBe('excluding_tax')
        ->and(setting('sales.taxes.calculation.based_on'))->toBe('shipping_origin')
        ->and(setting('sales.shipping.origin.country'))->toBe('MU')
        ->and(setting('sales.taxes.default_destination_calculation.country'))->toBe('MU')
        ->and((string) setting('sales.taxes.categories.product'))->toBe((string) standardCategory()->id)
        ->and((string) setting('sales.taxes.categories.shipping'))->toBe((string) standardCategory()->id)
        ->and(Tax::isInclusiveTaxProductPrices())->toBeFalse();
});

it('should create an exempt category with a zero rate for products that are not taxed', function () {
    taxSetup()->apply(TaxSetup::MODE_ADDED, 'MU', 'VAT', 15.0);

    $exempt = TaxCategory::query()->where('code', 'exempt')->firstOrFail();

    expect($exempt->tax_rates()->count())->toBe(1)
        ->and((float) $exempt->tax_rates()->first()->tax_rate)->toBe(0.0);
});

it('should update the rate in place when the owner changes it, leaving no stale copy', function () {
    taxSetup()->apply(TaxSetup::MODE_ADDED, 'MU', 'VAT', 15.0);

    $before = TaxRate::query()->count();

    taxSetup()->apply(TaxSetup::MODE_ADDED, 'MU', 'VAT', 16.5);

    expect(TaxRate::query()->count())->toBe($before)
        ->and(standardCategory()->tax_rates()->count())->toBe(1)
        ->and(standardRateFor('MU'))->tax_rate->toEqual(16.5);
});

it('should not fail when an owner-made rate already uses the identifier', function () {
    TaxRate::factory()->create(['identifier' => 'VAT 15% (MU)', 'country' => 'MU', 'state' => '*', 'zip_code' => '*', 'tax_rate' => 1]);

    taxSetup()->apply(TaxSetup::MODE_ADDED, 'MU', 'VAT', 15.0);

    expect(standardRateFor('MU'))->tax_rate->toEqual(15);
});

// ============================================================================
// Prices That Include Tax
// ============================================================================

it('should treat the prices as including tax and show them that way', function () {
    taxSetup()->apply(TaxSetup::MODE_INCLUDED, 'GB', 'VAT', 20.0);

    expect(Tax::isInclusiveTaxProductPrices())->toBeTrue()
        ->and(Tax::isInclusiveTaxShippingPrices())->toBeTrue()
        ->and(setting('sales.taxes.shopping_cart.display_prices'))->toBe('including_tax')
        ->and(setting('sales.taxes.sales.display_prices'))->toBe('including_tax')
        ->and(standardRateFor('GB'))->tax_rate->toEqual(20);
});

it('should go back to prices without tax when the owner switches to adding tax', function () {
    taxSetup()->apply(TaxSetup::MODE_INCLUDED, 'GB', 'VAT', 20.0);
    taxSetup()->apply(TaxSetup::MODE_ADDED, 'GB', 'VAT', 20.0);

    expect(Tax::isInclusiveTaxProductPrices())->toBeFalse()
        ->and(setting('sales.taxes.shopping_cart.display_prices'))->toBe('excluding_tax');
});

// ============================================================================
// Rates By Country
// ============================================================================

it('should look up the rate by the address the order ships to', function () {
    taxSetup()->apply(TaxSetup::MODE_BY_COUNTRY, 'MU');

    expect(setting('sales.taxes.calculation.based_on'))->toBe('shipping_address');
});

it('should keep the rates already in the category and add one for every other country', function () {
    taxSetup()->apply(TaxSetup::MODE_ADDED, 'MU', 'VAT', 15.0);
    taxSetup()->apply(TaxSetup::MODE_BY_COUNTRY, 'MU', null, null, 5.0);

    expect(standardRateFor('MU'))->tax_rate->toEqual(15)
        ->and(standardRateFor('FR'))->tax_rate->toEqual(5);
});

it('should charge nothing to countries without a rate when no rate for other countries is given', function () {
    taxSetup()->apply(TaxSetup::MODE_ADDED, 'MU', 'VAT', 15.0);
    taxSetup()->apply(TaxSetup::MODE_BY_COUNTRY, 'MU', null, null, 5.0);
    taxSetup()->apply(TaxSetup::MODE_BY_COUNTRY, 'MU');

    expect(standardRateFor('FR'))->toBeNull()
        ->and(standardRateFor('MU'))->tax_rate->toEqual(15)
        ->and(taxSetup()->current()['other_rate'])->toBeNull();
});

it('should replace the rates by country with the single local rate when the owner goes back to one rate', function () {
    taxSetup()->apply(TaxSetup::MODE_BY_COUNTRY, 'MU', null, null, 5.0);
    taxSetup()->apply(TaxSetup::MODE_ADDED, 'MU', 'VAT', 15.0);

    expect(standardCategory()->tax_rates()->count())->toBe(1)
        ->and(standardRateFor('FR'))->toBeNull();
});

// ============================================================================
// Reading And Announcing The Choice
// ============================================================================

it('should report the current choice back for the screen', function () {
    taxSetup()->apply(TaxSetup::MODE_INCLUDED, 'ZA', 'VAT', 15.0);

    expect(taxSetup()->current())->toMatchArray([
        'mode' => 'included',
        'confirmed' => true,
        'country' => 'ZA',
        'name' => 'VAT',
        'rate' => 15.0,
    ]);
});

it('should announce that the configuration changed so cached pages are flushed', function () {
    $announced = false;

    Event::listen('core.configuration.save.after', function () use (&$announced) {
        $announced = true;
    });

    taxSetup()->apply(TaxSetup::MODE_NONE);

    expect($announced)->toBeTrue();
});
