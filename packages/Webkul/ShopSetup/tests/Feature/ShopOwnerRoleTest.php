<?php

use Webkul\ShopSetup\Roles\ShopOwnerRole;
use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;

use function Pest\Laravel\get;

/**
 * An admin working under the shop owner role, signed in.
 */
function signInAsShopOwner(): Admin
{
    $admin = Admin::factory()->create();

    app(ShopOwnerRole::class)->assignTo($admin->id);

    test()->loginAsAdmin($admin->fresh());

    return $admin->fresh();
}

// ============================================================================
// The Permissions
// ============================================================================

it('should give the owner what runs a shop day to day', function (string $permission) {
    expect(app(ShopOwnerRole::class)->permissions())->toContain($permission);
})->with([
    'dashboard', 'catalog', 'catalog.products', 'catalog.categories', 'sales.orders', 'sales.invoices', 'sales.shipments',
    'customers.customers', 'cms', 'marketing.promotions.cart_rules', 'appearance.themes', 'appearance.sections',
    'configuration', 'settings.shop_tax', 'settings.taxes.tax_rates',
]);

it('should keep the technical settings away from the owner', function (string $permission) {
    expect(app(ShopOwnerRole::class)->permissions())->not->toContain($permission);
})->with([
    'settings.channels', 'settings.locales', 'settings.currencies', 'settings.exchange_rates', 'settings.inventory_sources',
    'settings.roles', 'settings.users', 'settings.data_transfer', 'settings.taxes.tax_categories', 'catalog.attributes', 'catalog.families',
]);

it('should leave out everything beneath a hidden permission', function () {
    $permissions = app(ShopOwnerRole::class)->permissions();

    expect(collect($permissions)->filter(fn (string $key) => str_starts_with($key, 'settings.channels.')))->toBeEmpty()
        ->and(collect($permissions)->filter(fn (string $key) => str_starts_with($key, 'catalog.attributes.')))->toBeEmpty();
});

it('should only hide permissions that exist, so an upstream rename cannot quietly un-hide one', function (string $hidden) {
    $keys = collect(config('acl'))->pluck('key');

    expect($keys->contains(fn (string $key) => $key === $hidden || str_starts_with($key, $hidden.'.')))->toBeTrue();
})->with(fn () => ShopOwnerRole::hiddenPermissions());

// ============================================================================
// Assigning The Role
// ============================================================================

it('should give the admin a custom role and no more than one role of the name', function () {
    $admin = Admin::factory()->create();

    app(ShopOwnerRole::class)->assignTo($admin->id);
    app(ShopOwnerRole::class)->assignTo($admin->id);

    $role = Role::query()->where('name', ShopOwnerRole::NAME)->get();

    expect($role)->toHaveCount(1)
        ->and($role->first()->permission_type)->toBe('custom')
        ->and($admin->fresh()->role_id)->toBe($role->first()->id);
});

it('should bring the permissions of an existing role up to date', function () {
    $admin = Admin::factory()->create();

    app(ShopOwnerRole::class)->assignTo($admin->id);

    Role::query()->where('name', ShopOwnerRole::NAME)->update(['permissions' => json_encode(['dashboard'])]);

    app(ShopOwnerRole::class)->assignTo($admin->id);

    expect(Role::query()->where('name', ShopOwnerRole::NAME)->first()->permissions)->toContain('settings.shop_tax');
});

// ============================================================================
// Working As The Owner
// ============================================================================

it('should let the owner reach the pages of a day to day shop', function (string $route) {
    signInAsShopOwner();

    get(route($route))->assertOk();
})->with([
    'dashboard' => 'admin.dashboard.index',
    'products' => 'admin.catalog.products.index',
    'orders' => 'admin.sales.orders.index',
    'customers' => 'admin.customers.customers.index',
    'tax setup' => 'admin.settings.shop_tax.index',
    'tax rates' => 'admin.settings.taxes.rates.index',
    'themes' => 'admin.appearance.themes.index',
]);

it('should refuse the owner the technical settings', function (string $route) {
    signInAsShopOwner();

    get(route($route))->assertUnauthorized();
})->with([
    'channels' => 'admin.settings.channels.index',
    'roles' => 'admin.settings.roles.index',
    'users' => 'admin.settings.users.index',
    'currencies' => 'admin.settings.currencies.index',
    'locales' => 'admin.settings.locales.index',
    'tax categories' => 'admin.settings.taxes.categories.index',
]);

it('should show the setup checklist on the owner dashboard until the shop is set up', function () {
    signInAsShopOwner();

    get(route('admin.dashboard.index'))
        ->assertOk()
        ->assertSeeText(trans('shop_setup::app.checklist.title'))
        ->assertSeeText(trans('shop_setup::app.checklist.items.tax.title'));
});
