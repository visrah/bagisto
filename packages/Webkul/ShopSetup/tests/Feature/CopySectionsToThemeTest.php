<?php

use Webkul\ShopSetup\Listeners\CopySectionsToTheme;
use Webkul\ShopSetup\Themes\ThemeHeroes;
use Webkul\Theme\Enums\SectionTypeEnum;
use Webkul\Theme\Repositories\SectionRepository;
use Webkul\Theme\Sections\StaticContent;

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

// ============================================================================
// A Hero Of The Theme's Own
// ============================================================================

const HERO_THEMES = ['apparel', 'electronics', 'food', 'home', 'beauty', 'kids', 'sports', 'jewelry', 'books'];

it('should swap the demo banner for the theme\'s own hero and keep every other section as it was', function (string $theme) {
    $source = sectionsOf('default');

    app(CopySectionsToTheme::class)->handle(channelOnTheme($theme));

    $copies = sectionsOf($theme);
    $banners = $source->where('type', SectionTypeEnum::IMAGE_CAROUSEL->value)->count();

    expect($copies->where('type', SectionTypeEnum::IMAGE_CAROUSEL->value)->count())->toBe($banners - 1)
        ->and($copies->where('type', SectionTypeEnum::STATIC_CONTENT->value)->count())
        ->toBe($source->where('type', SectionTypeEnum::STATIC_CONTENT->value)->count() + 1);

    $hero = $copies->first(fn ($section) => str_contains((string) data_get($section->translations->first()->options, 'html'), "vh-{$theme}"));

    expect($hero)->not->toBeNull()
        ->and($hero->translations->first()->draft_options)->toBeNull();
})->with(HERO_THEMES);

it('should give the hero a button to the shop and keep it intact through the section sanitiser', function () {
    app(CopySectionsToTheme::class)->handle(channelOnTheme('apparel'));

    $options = sectionsOf('apparel')
        ->map(fn ($section) => $section->translations->first()->options)
        ->first(fn ($options) => str_contains((string) data_get($options, 'html'), 'vh-apparel'));

    $clean = (new StaticContent)->sanitize($options);

    expect($clean['html'])->toContain('class="vh-button"')->toContain('href=')
        ->and($clean['html'])->toContain('vh-title')
        ->and($clean['css'])->toContain('.vh-apparel');
});

it('should give every store theme a hero that is its own composition', function () {
    $heroes = collect(HERO_THEMES)->mapWithKeys(fn (string $theme) => [$theme => app(ThemeHeroes::class)->for($theme, '/shop')]);

    expect($heroes->pluck('html')->unique())->toHaveCount(count(HERO_THEMES))
        ->and($heroes->map(fn ($hero) => preg_replace('/vh-[a-z]+/', '', $hero['css']))->unique()->count())->toBeGreaterThan(1);

    foreach ($heroes as $theme => $hero) {
        expect($hero['css'])->toContain(".vh-{$theme}")
            ->and($hero['html'])->toContain('href="/shop"');
    }
});

it('should draw heroes with the theme variables only, so they follow the theme and need no image', function (string $theme) {
    $css = app(ThemeHeroes::class)->for($theme, '/shop')['css'];

    expect($css)->not->toMatch('/#[0-9a-f]{3,8}\b/i')
        ->and($css)->not->toContain('url(')
        ->and($css)->toContain('var(--color-navyBlue)');
})->with(HERO_THEMES);

it('should leave the default theme without a hero of its own', function () {
    expect(app(ThemeHeroes::class)->has('default'))->toBeFalse()
        ->and(app(ThemeHeroes::class)->for('default', '/shop'))->toBeNull();
});

it('should give a theme copied before it had a hero that hero while its banner is still the demo one', function () {
    foreach (sectionsOf('default') as $section) {
        $copy = $section->replicateWithTranslations();
        $copy->theme_code = 'apparel';
        $copy->save();
    }

    expect(sectionsOf('apparel')->where('type', SectionTypeEnum::STATIC_CONTENT->value)->count())
        ->toBe(sectionsOf('default')->where('type', SectionTypeEnum::STATIC_CONTENT->value)->count());

    app(CopySectionsToTheme::class)->handle(channelOnTheme('apparel'));

    expect(sectionsOf('apparel')->where('type', SectionTypeEnum::STATIC_CONTENT->value)->count())
        ->toBe(sectionsOf('default')->where('type', SectionTypeEnum::STATIC_CONTENT->value)->count() + 1);

    $count = sectionsOf('apparel')->count();

    app(CopySectionsToTheme::class)->handle(channelOnTheme('apparel'));

    expect(sectionsOf('apparel')->count())->toBe($count)
        ->and(sectionsOf('apparel')->where('type', SectionTypeEnum::STATIC_CONTENT->value)->count())
        ->toBe(sectionsOf('default')->where('type', SectionTypeEnum::STATIC_CONTENT->value)->count() + 1);
});

it('should leave a banner the owner has changed alone', function () {
    foreach (sectionsOf('default') as $section) {
        $copy = $section->replicateWithTranslations();
        $copy->theme_code = 'food';
        $copy->save();
    }

    $banner = sectionsOf('food')->firstWhere('type', SectionTypeEnum::IMAGE_CAROUSEL->value);

    foreach ($banner->translations as $translation) {
        $translation->update(['options' => ['images' => [['image' => 'themes/food/own.webp', 'title' => 'Mine', 'link' => '#']]]]);
    }

    app(CopySectionsToTheme::class)->handle(channelOnTheme('food'));

    expect(sectionsOf('food')->firstWhere('id', $banner->id)->type)->toBe(SectionTypeEnum::IMAGE_CAROUSEL->value);
});
