<?php

use Webkul\ShopSetup\Tax\TaxSetup;

use function Pest\Laravel\get;
use function Pest\Laravel\put;

// ============================================================================
// Opening The Page
// ============================================================================

it('should show the four ways of charging tax to an admin', function () {
    $this->loginAsAdmin();

    get(route('admin.settings.shop_tax.index'))
        ->assertOk()
        ->assertSeeText(trans('shop_setup::app.tax.title'))
        ->assertSee('v-shop-tax', false);
});

it('should hand the screen the current choice and the country presets', function () {
    $this->loginAsAdmin();

    app(TaxSetup::class)->apply(TaxSetup::MODE_ADDED, 'MU', 'VAT', 15.0);

    get(route('admin.settings.shop_tax.index'))
        ->assertOk()
        ->assertSee('"mode":"added"', false)
        ->assertDontSee('"MU":{"name":"VAT","rate":15', false);
});

it('should send a guest to the login page', function () {
    get(route('admin.settings.shop_tax.index'))->assertRedirect(route('admin.session.create'));
});

it('should refuse an admin whose role does not include the tax setup', function () {
    $this->loginAsAdminWithPermissions(['dashboard']);

    get(route('admin.settings.shop_tax.index'))->assertUnauthorized();
});

it('should open for an admin whose role includes the tax setup', function () {
    $this->loginAsAdminWithPermissions(['dashboard', 'settings', 'settings.shop_tax']);

    get(route('admin.settings.shop_tax.index'))->assertOk();
});

// ============================================================================
// Saving The Choice
// ============================================================================

it('should apply a valid choice and return to the page', function () {
    $this->loginAsAdmin();

    put(route('admin.settings.shop_tax.update'), ['mode' => 'added', 'country' => 'MU', 'name' => 'VAT', 'rate' => '15'])
        ->assertRedirect(route('admin.settings.shop_tax.index'));

    expect(app(TaxSetup::class)->current())->toMatchArray(['mode' => 'added', 'confirmed' => true, 'country' => 'MU', 'rate' => 15.0]);
});

it('should accept the choice of charging no tax without a country', function () {
    $this->loginAsAdmin();

    put(route('admin.settings.shop_tax.update'), ['mode' => 'none'])->assertSessionHasNoErrors();

    expect(app(TaxSetup::class)->current()['mode'])->toBe('none');
});

it('should reject a taxed choice without a country, a name and a rate', function () {
    $this->loginAsAdmin();

    put(route('admin.settings.shop_tax.update'), ['mode' => 'included'])
        ->assertSessionHasErrors(['country', 'name', 'rate']);
});

it('should reject a mode the shop does not offer, an unknown country and a rate out of range', function (array $input, string $field) {
    $this->loginAsAdmin();

    put(route('admin.settings.shop_tax.update'), $input)->assertSessionHasErrors($field);
})->with([
    'unknown mode' => [['mode' => 'barter'], 'mode'],
    'unknown country' => [['mode' => 'added', 'country' => 'ZZ', 'name' => 'VAT', 'rate' => '15'], 'country'],
    'rate of zero' => [['mode' => 'added', 'country' => 'MU', 'name' => 'VAT', 'rate' => '0'], 'rate'],
    'rate above range' => [['mode' => 'added', 'country' => 'MU', 'name' => 'VAT', 'rate' => '120'], 'rate'],
]);
