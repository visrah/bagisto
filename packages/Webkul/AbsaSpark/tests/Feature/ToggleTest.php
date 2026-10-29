<?php

use Webkul\AbsaSpark\Services\AbsaSettings;
use Webkul\Payment\Facades\Payment;

use function Pest\Laravel\get;
use function Pest\Laravel\put;

// ============================================================================
// Switching Absa Spark On And Off
// ============================================================================

it('switches Absa Spark off and on again from the back office without losing the credentials', function () {
    $this->loginAsAdmin();
    $this->enableAbsa();
    $settings = app(AbsaSettings::class);

    put(route('admin.settings.absa_spark.toggle'), ['active' => '0'])->assertSessionHas('success');

    expect($settings->enabled())->toBeFalse()
        ->and($settings->apiKey())->toBe('test-key')
        ->and($settings->secret())->toBe('test-secret')
        ->and($settings->storeId())->toBe('STORE-1');

    put(route('admin.settings.absa_spark.toggle'), ['active' => '1'])->assertSessionHas('success');

    expect($settings->enabled())->toBeTrue();
});

it('offers Absa to customers only while it is switched on', function () {
    $this->loginAsAdmin();
    $this->enableAbsa();

    expect(collect(Payment::getPaymentMethods())->pluck('method'))->toContain('absaspark');

    put(route('admin.settings.absa_spark.toggle'), ['active' => '0']);

    expect(collect(Payment::getPaymentMethods())->pluck('method'))->not->toContain('absaspark');

    put(route('admin.settings.absa_spark.toggle'), ['active' => '1']);

    expect(collect(Payment::getPaymentMethods())->pluck('method'))->toContain('absaspark');
});

it('refuses to switch Absa on before the credentials are saved', function () {
    $this->loginAsAdmin();

    put(route('admin.settings.absa_spark.toggle'), ['active' => '1'])->assertSessionHas('error');

    expect(app(AbsaSettings::class)->enabled())->toBeFalse();
});

it('rejects a toggle value that is not on or off', function () {
    $this->loginAsAdmin();
    $this->enableAbsa();

    put(route('admin.settings.absa_spark.toggle'), ['active' => 'maybe'])->assertSessionHasErrors('active');
    put(route('admin.settings.absa_spark.toggle'), [])->assertSessionHasErrors('active');

    expect(app(AbsaSettings::class)->enabled())->toBeTrue();
});

it('shows Absa with a working switch on the Payments page and reflects its state', function () {
    config(['shop_setup.admin_mode' => 'simple']);
    $this->loginAsAdmin();
    $this->enableAbsa();

    get(route('admin.settings.merchant.payments'))
        ->assertOk()
        ->assertSee('Card payment')
        ->assertSee('role="switch"', false)
        ->assertSee('aria-checked="true"', false)
        ->assertSee(route('admin.settings.absa_spark.toggle'), false);

    put(route('admin.settings.absa_spark.toggle'), ['active' => '0']);

    get(route('admin.settings.merchant.payments'))->assertOk()->assertSee('aria-checked="false"', false);
});

it('blocks guests and staff without permission from the toggle', function () {
    $this->put(route('admin.settings.absa_spark.toggle'), ['active' => '1'])->assertRedirect(route('admin.session.create'));

    $this->loginAsAdminWithPermissions(['dashboard']);

    $this->put(route('admin.settings.absa_spark.toggle'), ['active' => '1'])->assertUnauthorized();
});
