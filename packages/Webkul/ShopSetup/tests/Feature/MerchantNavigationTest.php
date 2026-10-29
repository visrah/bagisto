<?php

use Illuminate\Support\Facades\DB;
use Webkul\ShopSetup\Navigation\MerchantNavigation;

use function Pest\Laravel\get;
use function Pest\Laravel\post;

// ============================================================================
// Merchant Navigation
// ============================================================================

it('offers six merchant areas using existing permissions and working routes', function () {
    $items = app(MerchantNavigation::class)->items();

    expect(collect($items)->filter(fn ($item) => ! str_contains($item['key'], '.'))->pluck('key')->all())
        ->toBe(['dashboard', 'catalog', 'sales', 'customers', 'appearance', 'configuration']);

    foreach ($items as $item) {
        expect(collect(config('acl'))->pluck('key'))->toContain($item['key']);
        expect(route($item['route']))->toBeString();
    }
});

it('keeps technical navigation out without deleting its permissions', function () {
    $keys = collect(app(MerchantNavigation::class)->items())->pluck('key');

    expect($keys)->not->toContain('settings.channels', 'catalog.families', 'marketing', 'reporting')
        ->and(collect(config('acl'))->pluck('key'))->toContain('settings.channels', 'catalog.families');
});

it('shows only working merchant settings and design destinations', function () {
    $this->loginAsAdmin();

    get(route('admin.settings.merchant.index'))->assertOk()
        ->assertSeeText(trans('shop_setup::merchant.payments'))
        ->assertSee(route('admin.settings.merchant.payments'), false);

    get(route('admin.appearance.merchant.index'))->assertOk()
        ->assertSee(route('admin.appearance.sections.index', core()->getDefaultChannel()->theme), false);
});

it('requires configuration permission for the merchant settings hub', function () {
    $this->loginAsAdminWithPermissions(['dashboard']);

    get(route('admin.settings.merchant.index'))->assertUnauthorized();
});

it('redirects anonymous visitors to login', function () {
    get(route('admin.settings.merchant.index'))->assertRedirect(route('admin.session.create'));
});

// ============================================================================
// Merchant Route Access
// ============================================================================

it('blocks guests and unprivileged staff from every merchant route', function (string $method, string $route) {
    $this->call($method, route($route))->assertRedirect(route('admin.session.create'));

    $this->loginAsAdminWithPermissions(['dashboard']);

    $this->call($method, route($route))->assertUnauthorized();
})->with([
    ['GET', 'admin.catalog.products.simple.create'],
    ['POST', 'admin.catalog.products.simple.store'],
    ['GET', 'admin.settings.merchant.index'],
    ['GET', 'admin.settings.merchant.branding'],
    ['PUT', 'admin.settings.merchant.update'],
    ['GET', 'admin.settings.merchant.payments'],
    ['GET', 'admin.catalog.inventory.index'],
    ['GET', 'admin.appearance.merchant.index'],
    ['GET', 'admin.settings.shop_tax.index'],
    ['PUT', 'admin.settings.shop_tax.update'],
]);

// ============================================================================
// Payment Status And Inventory Pages
// ============================================================================

it('shows each payment method with its saved state and never claims verification', function () {
    $this->loginAsAdmin();

    foreach (['cashondelivery' => 1, 'moneytransfer' => 0] as $code => $active) {
        DB::table('core_config')->insert(['code' => 'sales.payment_methods.'.$code.'.active', 'value' => $active, 'channel_code' => 'default', 'locale_code' => 'en', 'created_at' => now(), 'updated_at' => now()]);
    }

    get(route('admin.settings.merchant.payments'))
        ->assertOk()
        ->assertSee(trans('shop_setup::merchant.payment-help'))
        ->assertSee(trans('shop_setup::merchant.enabled'))
        ->assertSee(trans('shop_setup::merchant.disabled'))
        ->assertDontSee('verified</');
});

it('lists managed stock at the default location with the lowest quantity first', function () {
    $this->loginAsAdmin();

    post(route('admin.catalog.products.simple.store'), [
        'name' => 'Low stock tea', 'description' => 'Tea.', 'price' => 100, 'quantity' => 2, 'status' => 1,
    ]);

    post(route('admin.catalog.products.simple.store'), [
        'name' => 'Plenty tea', 'description' => 'Tea.', 'price' => 100, 'quantity' => 50, 'status' => 1,
    ]);

    get(route('admin.catalog.inventory.index'))
        ->assertOk()
        ->assertSeeInOrder(['Low stock tea', 'Plenty tea']);
});
