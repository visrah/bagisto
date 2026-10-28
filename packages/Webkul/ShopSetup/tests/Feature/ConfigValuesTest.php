<?php

use Illuminate\Support\Facades\DB;
use Webkul\ShopSetup\Support\ConfigValues;

// ============================================================================
// Where A Value Is Stored
// ============================================================================

it('should store a channel-bound field for the default channel, where the application reads it', function () {
    app(ConfigValues::class)->set('sales.shipping.origin.city', 'Port Louis');

    $row = DB::table('core_config')->where('code', 'sales.shipping.origin.city')->first();

    expect($row->channel_code)->toBe(core()->getDefaultChannelCode())
        ->and(core()->getConfigData('sales.shipping.origin.city'))->toBe('Port Louis');
});

it('should store a field that does not depend on the channel without one', function () {
    app(ConfigValues::class)->set('sales.taxes.calculation.based_on', 'shipping_origin');

    $row = DB::table('core_config')->where('code', 'sales.taxes.calculation.based_on')->first();

    expect($row->channel_code)->toBeNull()
        ->and(core()->getConfigData('sales.taxes.calculation.based_on'))->toBe('shipping_origin');
});

it('should store a setting the application does not define for all channels', function () {
    app(ConfigValues::class)->set('shop_setup.example', 'yes');

    expect(app(ConfigValues::class)->get('shop_setup.example'))->toBe('yes')
        ->and(DB::table('core_config')->where('code', 'shop_setup.example')->value('channel_code'))->toBeNull();
});

// ============================================================================
// Replacing And Defaulting
// ============================================================================

it('should replace a value in place rather than add a second row', function () {
    app(ConfigValues::class)->set('sales.shipping.origin.city', 'Port Louis');
    app(ConfigValues::class)->set('sales.shipping.origin.city', 'Curepipe');

    expect(DB::table('core_config')->where('code', 'sales.shipping.origin.city')->count())->toBe(1)
        ->and(core()->getConfigData('sales.shipping.origin.city'))->toBe('Curepipe');
});

it('should only fill in a default when nothing is stored', function () {
    app(ConfigValues::class)->set('sales.shipping.origin.city', 'Port Louis');
    app(ConfigValues::class)->default('sales.shipping.origin.city', 'Curepipe');
    app(ConfigValues::class)->default('sales.shipping.origin.state', 'Plaines Wilhems');

    expect(core()->getConfigData('sales.shipping.origin.city'))->toBe('Port Louis')
        ->and(core()->getConfigData('sales.shipping.origin.state'))->toBe('Plaines Wilhems');
});

// ============================================================================
// Reads The Repository Cached
// ============================================================================

it('should make a new value visible to a read that was cached before the write', function () {
    expect(core()->getConfigData('sales.shipping.origin.zipcode'))->toBeNull();

    app(ConfigValues::class)->set('sales.shipping.origin.zipcode', '11101');

    expect(core()->getConfigData('sales.shipping.origin.zipcode'))->toBe('11101');
});

it('should make a changed value visible to a read that was cached before the write', function () {
    app(ConfigValues::class)->set('sales.shipping.origin.zipcode', '11101');

    expect(core()->getConfigData('sales.shipping.origin.zipcode'))->toBe('11101');

    app(ConfigValues::class)->set('sales.shipping.origin.zipcode', '72201');

    expect(core()->getConfigData('sales.shipping.origin.zipcode'))->toBe('72201');
});
