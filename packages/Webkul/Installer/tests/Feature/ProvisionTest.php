<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Webkul\Installer\Console\Commands\Provision;
use Webkul\Installer\Helpers\DatabaseManager;
use Webkul\Installer\Helpers\ShopDefaults;

/**
 * Run the command's atomic step against a stand-in database manager whose seeding writes one marker row.
 */
function provisionWith(bool $seeds, bool $createsAdmin): bool
{
    $manager = Mockery::mock(DatabaseManager::class);

    $manager->shouldReceive('seed')->andReturnUsing(function () use ($seeds) {
        DB::table('core_config')->insert([
            'code' => 'test.provision.marker',
            'value' => '1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $seeds;
    });

    $manager->shouldReceive('createAdminUser')->andReturn($createsAdmin);

    $method = new ReflectionMethod(Provision::class, 'provisionAtomically');

    return $method->invoke(app(Provision::class), $manager, [], ['name' => 'Admin', 'email' => 'a@example.com', 'password' => 'secret-pass']);
}

function configValue(string $code): ?string
{
    return DB::table('core_config')->where('code', $code)->whereNull('channel_code')->value('value');
}

// ============================================================================
// Atomic Provisioning
// ============================================================================

it('should commit what the seeders wrote when provisioning succeeds', function () {
    expect(provisionWith(true, true))->toBeTrue()
        ->and(configValue('test.provision.marker'))->toBe('1');
});

it('should roll back what the seeders wrote when a seeder reports failure', function () {
    expect(provisionWith(false, true))->toBeFalse()
        ->and(configValue('test.provision.marker'))->toBeNull();
});

it('should roll back what the seeders wrote when the admin cannot be created', function () {
    expect(provisionWith(true, false))->toBeFalse()
        ->and(configValue('test.provision.marker'))->toBeNull();
});

it('should write the shop defaults only when provisioning succeeded', function () {
    DB::table('core_config')->where('code', 'sales.payment_methods.stripe.active')->delete();

    provisionWith(false, true);

    expect(configValue('sales.payment_methods.stripe.active'))->toBeNull();

    provisionWith(true, true);

    expect(configValue('sales.payment_methods.stripe.active'))->toBe('0');
});

// ============================================================================
// Shop Defaults
// ============================================================================

it('should switch off every gateway that needs credentials and leave the offline methods alone', function () {
    DB::table('core_config')->where('code', 'like', 'sales.payment_methods.%')->delete();

    app(ShopDefaults::class)->apply();

    foreach (['paypal_smart_button', 'paypal_standard', 'stripe', 'razorpay', 'payu', 'phonepe', 'payglocal'] as $method) {
        expect(configValue("sales.payment_methods.{$method}.active"))->toBe('0');
    }

    expect(configValue('sales.payment_methods.cashondelivery.active'))->toBeNull()
        ->and(configValue('sales.payment_methods.moneytransfer.active'))->toBeNull();
});

it('should take the origin country, store name and VAT number from the options', function () {
    app(ShopDefaults::class)->apply(['country' => 'mu', 'store_name' => 'Cat Cafe', 'vat_number' => 'VAT123']);

    expect(configValue('sales.shipping.origin.country'))->toBe('MU')
        ->and(configValue('sales.taxes.default_destination_calculation.country'))->toBe('MU')
        ->and(configValue('sales.shipping.origin.store_name'))->toBe('Cat Cafe')
        ->and(configValue('sales.shipping.origin.vat_number'))->toBe('VAT123');
});

it('should ignore a country that is not a two letter code and options that are empty', function () {
    DB::table('core_config')->where('code', 'like', 'sales.shipping.origin.%')->delete();

    app(ShopDefaults::class)->apply(['country' => 'Mauritius', 'store_name' => '  ', 'vat_number' => null]);

    expect(configValue('sales.shipping.origin.country'))->toBeNull()
        ->and(configValue('sales.shipping.origin.store_name'))->toBeNull()
        ->and(configValue('sales.shipping.origin.vat_number'))->toBeNull();
});

it('should not duplicate a value when it is applied twice', function () {
    app(ShopDefaults::class)->apply(['country' => 'MU']);
    app(ShopDefaults::class)->apply(['country' => 'GB']);

    expect(DB::table('core_config')->where('code', 'sales.shipping.origin.country')->count())->toBe(1)
        ->and(configValue('sales.shipping.origin.country'))->toBe('GB');
});

// ============================================================================
// Reporting Nothing Home
// ============================================================================

it('should not report the installation to a third party', function () {
    expect(Event::hasListeners('bagisto.installed'))->toBeFalse()
        ->and(class_exists('Webkul\Installer\Listeners\Installer'))->toBeFalse();
});
