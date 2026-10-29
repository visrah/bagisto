<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Webkul\ShopSetup\Support\ConfigValues;

use function Pest\Laravel\get;
use function Pest\Laravel\put;

// ============================================================================
// Store Information
// ============================================================================

it('saves merchant information without changing the internal store relationships', function () {
    Storage::fake();
    $this->loginAsAdmin();
    $channel = core()->getDefaultChannel();
    $locales = $channel->locales->pluck('id')->all();
    $currencies = $channel->currencies->pluck('id')->all();
    $sources = $channel->inventory_sources->pluck('id')->all();

    put(route('admin.settings.merchant.update'), [
        'name' => 'Island shop', 'description' => 'Local goods.', 'email' => 'hello@example.test',
        'contact' => '+230 5555 1234', 'address' => 'Main Road', 'city' => 'Rose Hill', 'country' => 'MU',
        'logo' => [UploadedFile::fake()->image('logo.png')],
        'base_currency_id' => 99999, 'hostname' => 'attacker.example',
    ])->assertSessionHasNoErrors()->assertRedirect(route('admin.settings.merchant.branding'));

    $saved = $channel->fresh();

    expect($saved->translate($channel->default_locale->code)->name)->toBe('Island shop')
        ->and($saved->base_currency_id)->toBe($channel->base_currency_id)
        ->and($saved->hostname)->toBe($channel->hostname)
        ->and($saved->locales->pluck('id')->all())->toBe($locales)
        ->and($saved->currencies->pluck('id')->all())->toBe($currencies)
        ->and($saved->inventory_sources->pluck('id')->all())->toBe($sources)
        ->and(app(ConfigValues::class)->get('sales.shipping.origin.city'))->toBe('Rose Hill');

    Storage::assertExists($saved->logo);
});

it('preserves the existing store logo when no replacement is uploaded', function () {
    Storage::fake();
    $this->loginAsAdmin();
    $channel = core()->getDefaultChannel();
    $channel->logo = 'channels/'.$channel->id.'/existing.png';
    $channel->save();
    Storage::put($channel->logo, 'existing');

    put(route('admin.settings.merchant.update'), [
        'name' => 'Island shop', 'email' => 'hello@example.test', 'address' => 'Main Road', 'city' => 'Rose Hill', 'country' => 'MU',
    ])->assertSessionHasNoErrors();

    expect($channel->fresh()->logo)->toBe($channel->logo);
    Storage::assertExists($channel->logo);
});

it('validates store settings and restricts who can save them', function () {
    $this->loginAsAdmin();
    put(route('admin.settings.merchant.update'), ['name' => 'Shop', 'email' => 'invalid'])->assertSessionHasErrors(['email', 'address', 'city', 'country']);

    $this->loginAsAdminWithPermissions(['dashboard']);
    get(route('admin.settings.merchant.branding'))->assertUnauthorized();
    put(route('admin.settings.merchant.update'), [])->assertUnauthorized();
});
