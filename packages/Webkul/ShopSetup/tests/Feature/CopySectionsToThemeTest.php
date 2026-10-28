<?php

use Webkul\ShopSetup\Listeners\CopySectionsToTheme;
use Webkul\Theme\Repositories\SectionRepository;

/**
 * The default channel as though its theme had just been switched.
 */
function channelOnTheme(string $code): object
{
    $channel = core()->getDefaultChannel();

    $channel->theme = $code;

    return $channel;
}

function sectionsOf(string $theme)
{
    return app(SectionRepository::class)->findWhere(['channel_id' => core()->getDefaultChannel()->id, 'theme_code' => $theme]);
}

// ============================================================================
// Giving A New Theme A Homepage
// ============================================================================

it('should copy the default theme\'s sections, with their options, to the theme the channel switched to', function () {
    $source = sectionsOf('default');

    expect($source)->not->toBeEmpty();

    app(CopySectionsToTheme::class)->handle(channelOnTheme('apparel'));

    $copies = sectionsOf('apparel');

    expect($copies)->toHaveCount($source->count())
        ->and($copies->pluck('type')->sort()->values()->all())->toBe($source->pluck('type')->sort()->values()->all())
        ->and($copies->first()->translations)->not->toBeEmpty();
});

it('should leave the source sections as they were', function () {
    $before = sectionsOf('default')->pluck('id')->all();

    app(CopySectionsToTheme::class)->handle(channelOnTheme('food'));

    expect(sectionsOf('default')->pluck('id')->all())->toBe($before);
});

it('should not copy again once the theme has sections, so an edit is never overwritten', function () {
    app(CopySectionsToTheme::class)->handle(channelOnTheme('kids'));

    $first = sectionsOf('kids')->pluck('id')->all();

    app(CopySectionsToTheme::class)->handle(channelOnTheme('kids'));

    expect(sectionsOf('kids')->pluck('id')->all())->toBe($first);
});

it('should do nothing for the default theme', function () {
    $before = sectionsOf('default')->count();

    app(CopySectionsToTheme::class)->handle(channelOnTheme('default'));

    expect(sectionsOf('default')->count())->toBe($before);
});
