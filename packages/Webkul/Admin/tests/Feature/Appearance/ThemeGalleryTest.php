<?php

use Illuminate\Support\Facades\Event;
use Webkul\Core\Models\Channel;
use Webkul\Core\Repositories\ChannelRepository;
use Webkul\Theme\Models\Section;
use Webkul\Theme\ThemeCatalog;

use function Pest\Laravel\get;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

// ============================================================================
// Gallery
// ============================================================================

it('should render the theme gallery', function () {
    $this->loginAsAdmin();

    get(route('admin.appearance.themes.index'))
        ->assertOk()
        ->assertSee(trans('admin::app.appearance.themes.index.title'))
        ->assertSee('Default');
});

// ============================================================================
// Catalog Status
// ============================================================================

it('should mark the theme a channel runs as active', function () {
    $channel = core()->getDefaultChannel();

    $channel->theme = 'default';

    $channel->save();

    $theme = app(ThemeCatalog::class)->find('default');

    expect($theme['status'])->toBe(ThemeCatalog::STATUS_ACTIVE);

    expect(collect($theme['active_on'])->pluck('id'))->toContain($channel->id);
});

it('should list only themes that ship with the store, so none is left to buy', function () {
    $themes = app(ThemeCatalog::class)->all();

    expect($themes)->not->toBeEmpty();

    foreach ($themes as $theme) {
        expect($theme['is_installed'])->toBeTrue()
            ->and($theme['status'])->not->toBe(ThemeCatalog::STATUS_AVAILABLE)
            ->and($theme['url'])->toBeNull()
            ->and($theme['tags'])->not->toContain('premium');
    }
});

// ============================================================================
// Impact Report
// ============================================================================

it('should report how many customizations a theme switch would leave behind', function () {
    $channel = Channel::factory()->create(['theme' => 'default']);

    Section::factory()->count(3)->create([
        'channel_id' => $channel->id,
        'theme_code' => 'default',
    ]);

    $this->loginAsAdmin();

    getJson(route('admin.appearance.themes.impact', 'apparel').'?channel_ids[]='.$channel->id)
        ->assertOk()
        ->assertJsonPath('impact.0.channel_id', $channel->id)
        ->assertJsonPath('impact.0.current_theme', config('themes.shop.default.name'))
        ->assertJsonPath('impact.0.customizations', 3);
});

// ============================================================================
// Activation
// ============================================================================

it('should activate an installed theme on a channel', function () {
    $channel = Channel::factory()->create(['theme' => 'something-else']);

    $this->loginAsAdmin();

    postJson(route('admin.appearance.themes.activate', 'default'), [
        'channel_ids' => [$channel->id],
    ])->assertOk();

    expect($channel->fresh()->theme)->toBe('default');
});

it('should announce a channel update so the channel and page caches are dropped', function () {
    $channel = Channel::factory()->create(['theme' => 'something-else']);

    Event::fake();

    $this->loginAsAdmin();

    postJson(route('admin.appearance.themes.activate', 'default'), [
        'channel_ids' => [$channel->id],
    ])->assertOk();

    Event::assertDispatched('core.channel.update.before');

    Event::assertDispatched('core.channel.update.after');
});

it('should read back the newly activated theme rather than a cached one', function () {
    $channel = Channel::factory()->create(['theme' => 'something-else']);

    app(ChannelRepository::class)->find($channel->id);

    $this->loginAsAdmin();

    postJson(route('admin.appearance.themes.activate', 'default'), [
        'channel_ids' => [$channel->id],
    ])->assertOk();

    expect(app(ChannelRepository::class)->find($channel->id)->theme)->toBe('default');
});

it('should refuse to activate a theme that is not installed', function () {
    $channel = Channel::factory()->create(['theme' => 'default']);

    $this->loginAsAdmin();

    postJson(route('admin.appearance.themes.activate', 'not-installed'), [
        'channel_ids' => [$channel->id],
    ])->assertNotFound();

    expect($channel->fresh()->theme)->toBe('default');
});

it('should reject an activation without a valid channel', function () {
    $this->loginAsAdmin();

    postJson(route('admin.appearance.themes.activate', 'default'), [
        'channel_ids' => [99999],
    ])->assertUnprocessable();
});

it('should activate a theme on several channels at once', function () {
    $first = Channel::factory()->create(['theme' => 'something-else']);

    $second = Channel::factory()->create(['theme' => 'something-else']);

    $this->loginAsAdmin();

    postJson(route('admin.appearance.themes.activate', 'default'), [
        'channel_ids' => [$first->id, $second->id],
    ])->assertOk();

    expect($first->fresh()->theme)->toBe('default');

    expect($second->fresh()->theme)->toBe('default');
});

it('should reject an activation with no channel selected', function () {
    $this->loginAsAdmin();

    postJson(route('admin.appearance.themes.activate', 'default'), [
        'channel_ids' => [],
    ])->assertUnprocessable();
});

// ============================================================================
// Impact Report
// ============================================================================

it('should leave a channel already on the theme out of the impact report', function () {
    $channel = Channel::factory()->create(['theme' => 'default']);

    Section::factory()->count(2)->create([
        'channel_id' => $channel->id,
        'theme_code' => 'default',
    ]);

    $this->loginAsAdmin();

    getJson(route('admin.appearance.themes.impact', 'default').'?channel_ids[]='.$channel->id)
        ->assertOk()
        ->assertJsonCount(0, 'impact');
});

// ============================================================================
// Catalog Details
// ============================================================================

it('should not carry a price, which the store page is the source of', function () {
    foreach (app(ThemeCatalog::class)->all() as $theme) {
        expect($theme)->not->toHaveKeys(['price', 'currency', 'is_paid']);
    }
});

it('should serve the bundled screenshot of an installed theme from the admin build', function () {
    $theme = app(ThemeCatalog::class)->find('default');

    expect($theme['screenshot'])->toContain('themes/admin/default/build/');

    expect($theme['screenshot'])->toEndWith('.jpg');
});

// ============================================================================
// Theme Codes
// ============================================================================

it('should offer the default theme and the nine store themes, and no marketplace theme', function () {
    $codes = app(ThemeCatalog::class)->all()->pluck('code');

    expect($codes)->toContain('default', 'apparel', 'electronics', 'food', 'home', 'beauty', 'kids', 'sports', 'jewelry', 'books');

    foreach (['fashion', 'breeze', 'commercia', 'adornments', 'perfume', 'electronic', 'elvix', 'autrivo', 'waggin'] as $paid) {
        expect($codes)->not->toContain($paid);
    }
});

it('should give every catalog theme a single, well formed code', function () {
    $codes = collect(require base_path('packages/Webkul/Theme/src/Resources/catalog.php'))->pluck('code');

    expect($codes->filter()->count())->toBe($codes->count())
        ->and($codes->duplicates())->toBeEmpty();

    foreach ($codes as $code) {
        expect($code)->toMatch('/^[a-z0-9]+(-[a-z0-9]+)*$/');
    }
});

it('should list every theme this installation registers under the code it is registered with', function () {
    $catalog = app(ThemeCatalog::class);

    expect($catalog->isInstalled(config('themes.shop-default')))->toBeTrue();

    foreach (array_keys(config('themes.shop')) as $code) {
        $theme = $catalog->find($code);

        expect($theme)->not->toBeNull()
            ->and($theme['is_installed'])->toBeTrue()
            ->and($catalog->isInstalled($code))->toBeTrue();
    }
});

// ============================================================================
// Impact Report
// ============================================================================

it('should name the channel current theme in the impact report by its catalog name', function () {
    $channel = Channel::factory()->create(['theme' => 'default']);

    Section::factory()->create([
        'channel_id' => $channel->id,
        'theme_code' => 'default',
    ]);

    $this->loginAsAdmin();

    getJson(route('admin.appearance.themes.impact', 'apparel').'?channel_ids[]='.$channel->id)
        ->assertOk()
        ->assertJsonPath('impact.0.current_theme', app(ThemeCatalog::class)->find('default')['name']);
});
