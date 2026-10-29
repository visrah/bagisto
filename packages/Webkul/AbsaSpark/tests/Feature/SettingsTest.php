<?php

use Illuminate\Support\Facades\DB;
use Webkul\AbsaSpark\Services\AbsaSettings;
use Webkul\User\Models\Role;

use function Pest\Laravel\get;
use function Pest\Laravel\put;

// ============================================================================
// Credentials Page
// ============================================================================

it('saves credentials encrypted and never shows them again', function () {
    $this->loginAsAdmin();

    put(route('admin.settings.absa_spark.update'), [
        'active' => '1', 'title' => 'Card payment', 'sandbox' => '0',
        'store_id' => 'STORE-9', 'api_key' => 'plain-key-123', 'secret' => 'plain-secret-456',
    ])->assertRedirect(route('admin.settings.absa_spark.index'));

    $stored = DB::table('core_config')->where('code', 'like', AbsaSettings::PREFIX.'%')->pluck('value', 'code');
    $settings = app(AbsaSettings::class);

    expect($stored[AbsaSettings::PREFIX.'api_key'])->toStartWith('enc:')->not->toContain('plain-key-123')
        ->and($stored[AbsaSettings::PREFIX.'secret'])->not->toContain('plain-secret-456')
        ->and($settings->apiKey())->toBe('plain-key-123')
        ->and($settings->secret())->toBe('plain-secret-456')
        ->and($settings->storeId())->toBe('STORE-9')
        ->and($settings->sandbox())->toBeFalse()
        ->and($settings->baseUrl())->toBe(AbsaSettings::LIVE_URL);

    get(route('admin.settings.absa_spark.index'))
        ->assertOk()
        ->assertDontSee('plain-key-123')
        ->assertDontSee('plain-secret-456')
        ->assertSee(trans('absa_spark::app.settings.saved-secret'));
});

it('keeps a saved secret when the field is left blank', function () {
    $this->loginAsAdmin();
    $this->enableAbsa();

    put(route('admin.settings.absa_spark.update'), [
        'active' => '1', 'title' => 'Card', 'sandbox' => '1', 'store_id' => 'STORE-2', 'api_key' => '', 'secret' => '',
    ])->assertSessionHasNoErrors();

    $settings = app(AbsaSettings::class);

    expect($settings->apiKey())->toBe('test-key')
        ->and($settings->secret())->toBe('test-secret')
        ->and($settings->storeId())->toBe('STORE-2');
});

it('refuses to enable Absa without every credential', function () {
    $this->loginAsAdmin();

    put(route('admin.settings.absa_spark.update'), [
        'active' => '1', 'title' => 'Card', 'sandbox' => '1', 'store_id' => 'STORE-2', 'api_key' => 'k', 'secret' => '',
    ])->assertSessionHasErrors('active');

    expect(app(AbsaSettings::class)->enabled())->toBeFalse();
});

it('treats an unreadable stored secret as missing', function () {
    DB::table('core_config')->insert(['code' => AbsaSettings::PREFIX.'secret', 'value' => 'enc:garbage', 'channel_code' => null, 'locale_code' => null, 'created_at' => now(), 'updated_at' => now()]);

    expect(app(AbsaSettings::class)->secret())->toBeNull();
});

it('blocks guests and staff without permission from the credentials page', function (string $method, string $route) {
    $this->call($method, route($route))->assertRedirect(route('admin.session.create'));

    $this->loginAsAdminWithPermissions(['dashboard']);

    $this->call($method, route($route))->assertUnauthorized();
})->with([
    ['GET', 'admin.settings.absa_spark.index'],
    ['PUT', 'admin.settings.absa_spark.update'],
]);

// ============================================================================
// Shop Owner Access
// ============================================================================

it('grants the credentials page to an existing shop owner role when the package is installed', function () {
    $role = Role::factory()->create(['name' => 'Shop owner', 'permission_type' => 'custom', 'permissions' => ['dashboard']]);

    (require base_path('packages/Webkul/AbsaSpark/src/Database/Migrations/2026_10_29_000100_grant_absa_spark_to_shop_owner.php'))->up();
    (require base_path('packages/Webkul/AbsaSpark/src/Database/Migrations/2026_10_29_000100_grant_absa_spark_to_shop_owner.php'))->up();

    expect($role->refresh()->permissions)->toBe(['dashboard', 'settings.absa_spark']);
});
