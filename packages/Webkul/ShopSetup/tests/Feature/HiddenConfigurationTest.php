<?php

use function Pest\Laravel\get;
use function Pest\Laravel\post;

// ============================================================================
// What The Screen Offers
// ============================================================================

it('should keep the storage, AI and cache sections off the configuration screen', function (string $key) {
    expect(system_config()->getItems()->pluck('key'))->not->toContain($key);
})->with(['file_management', 'magic_ai', 'cache_management', 'about']);

it('should keep the sections a shop needs on the configuration screen', function (string $key) {
    expect(system_config()->getItems()->pluck('key'))->toContain($key);
})->with(['sales', 'emails', 'general']);

it('should answer not found for a hidden section opened by address', function () {
    $this->loginAsAdmin();

    get(route('admin.configuration.index', ['slug' => 'file_management', 'slug2' => 'general']))->assertNotFound();
});

it('should refuse a hand-made request that saves a hidden section', function () {
    $this->loginAsAdmin();

    post(route('admin.configuration.store'), ['file_management' => ['general' => ['settings' => ['anything' => '1']]]])->assertForbidden();
});

// ============================================================================
// How A Section Is Matched
// ============================================================================

it('should hide a key together with everything beneath it and nothing beside it', function () {
    config(['system_config_hidden' => ['general.sitemap']]);

    expect(system_config()->isHidden('general.sitemap'))->toBeTrue()
        ->and(system_config()->isHidden('general.sitemap.settings'))->toBeTrue()
        ->and(system_config()->isHidden('general.sitemaps'))->toBeFalse()
        ->and(system_config()->isHidden('general.content'))->toBeFalse();
});

it('should hide nothing when no section is listed', function () {
    config(['system_config_hidden' => []]);

    expect(system_config()->isHidden('file_management'))->toBeFalse();
});
