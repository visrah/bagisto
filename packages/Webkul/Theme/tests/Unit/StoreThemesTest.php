<?php

use Webkul\Theme\ThemeCatalog;

const STORE_THEME_CODES = ['apparel', 'electronics', 'food', 'home', 'beauty', 'kids', 'sports', 'jewelry', 'books'];

function themesStylesheet(): string
{
    return file_get_contents(base_path('packages/Webkul/Shop/src/Resources/assets/css/themes.css'));
}

/**
 * The declarations of the block a theme sets on the root element. The last theme also ends the selector list of the shared
 * rule, so the block is the one that names the theme's own surface colour.
 */
function themeBlock(string $code): string
{
    preg_match_all('/^\[data-theme="'.$code.'"\] \{(.*?)^\}/ms', themesStylesheet(), $matches);

    return collect($matches[1])->first(fn (string $block) => str_contains($block, '--theme-surface:')) ?? '';
}

/**
 * A colour a theme block sets as a hex value, as linear sRGB.
 *
 * @return array{0: float, 1: float, 2: float}
 */
function themeColor(string $code, string $variable): array
{
    preg_match('/'.preg_quote($variable, '/').':\s*#([0-9a-f]{6});/i', themeBlock($code), $matches);

    return array_map(
        fn (string $channel) => (($c = hexdec($channel) / 255) <= 0.04045) ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4,
        str_split($matches[1] ?? '000000', 2)
    );
}

function themeContrast(array $a, array $b): float
{
    $luminance = fn (array $c) => 0.2126 * $c[0] + 0.7152 * $c[1] + 0.0722 * $c[2];

    return (max($luminance($a), $luminance($b)) + 0.05) / (min($luminance($a), $luminance($b)) + 0.05);
}

// ============================================================================
// Registry And Catalog
// ============================================================================

it('should register every store theme on the default theme\'s views and build', function (string $code) {
    $themes = config('themes.shop');

    expect($themes)->toHaveKey($code)
        ->and($themes[$code]['name'])->not->toBeEmpty()
        ->and($themes[$code]['assets_path'])->toBe($themes['default']['assets_path'])
        ->and($themes[$code]['vite'])->toBe($themes['default']['vite']);
})->with(STORE_THEME_CODES);

it('should list every store theme in the gallery with a description and a preview', function (string $code) {
    $theme = app(ThemeCatalog::class)->find($code);

    expect($theme)->not->toBeNull()
        ->and($theme['description'])->not->toBeEmpty()
        ->and($theme['screenshot'])->toEndWith("/images/store-themes/{$code}.webp")
        ->and(public_path("images/store-themes/{$code}.webp"))->toBeFile();
})->with(STORE_THEME_CODES);

it('should not reuse the code of a listed premium theme', function () {
    $premium = collect(require base_path('packages/Webkul/Theme/src/Resources/catalog.php'))
        ->filter(fn (array $entry) => in_array('premium', $entry['tags'], true))
        ->pluck('code');

    expect($premium->intersect(STORE_THEME_CODES))->toBeEmpty();
});

// ============================================================================
// The Stylesheet
// ============================================================================

it('should give every store theme a block of the variables the storefront reads', function (string $code) {
    $block = themeBlock($code);

    foreach (['--theme-surface', '--theme-ink', '--color-white', '--color-navyBlue', '--color-lightOrange', '--color-darkBlue', '--font-poppins', '--font-dmserif', '--radius-md', '--radius-xl'] as $variable) {
        expect($block)->toContain($variable.':');
    }
})->with(STORE_THEME_CODES);

it('should only point at font files that exist', function () {
    preg_match_all('/url\("\.\.\/fonts\/([^"]+)"\)/', themesStylesheet(), $matches);

    expect($matches[1])->not->toBeEmpty();

    foreach ($matches[1] as $file) {
        expect(base_path("packages/Webkul/Shop/src/Resources/assets/fonts/{$file}"))->toBeFile();
    }
});

it('should keep text, buttons and links readable in every store theme', function (string $code) {
    $surface = themeColor($code, '--theme-surface');

    expect(themeContrast(themeColor($code, '--theme-ink'), $surface))->toBeGreaterThanOrEqual(7.0)
        ->and(themeContrast($surface, themeColor($code, '--color-navyBlue')))->toBeGreaterThanOrEqual(4.5)
        ->and(themeContrast(themeColor($code, '--color-darkBlue'), $surface))->toBeGreaterThanOrEqual(4.5);
})->with(STORE_THEME_CODES);

// ============================================================================
// Reaching The Storefront
// ============================================================================

it('should set the theme code on the root element and load no font from a third party', function () {
    $layout = file_get_contents(base_path('packages/Webkul/Shop/src/Resources/views/components/layouts/index.blade.php'));

    expect($layout)->toContain('data-theme=')
        ->and($layout)->not->toContain('fonts.googleapis.com')
        ->and($layout)->not->toContain('fonts.gstatic.com');
});

it('should leave no theme colour or font hard-coded in the seeded homepage sections', function () {
    $seeder = file_get_contents(base_path('packages/Webkul/Installer/src/Database/Seeders/Shop/SectionTableSeeder.php'));

    $css = collect(explode("\n", $seeder))->filter(fn (string $line) => str_contains($line, "'css' =>"))->implode("\n");

    expect(strtolower($css))->not->toContain('#060c3b')
        ->and($css)->not->toContain('DM Serif Display')
        ->and($css)->not->toContain('#E8EDFE');
});

// ============================================================================
// Layout Variants
// ============================================================================

const LAYOUT_VARIANTS = [
    'header' => ['classic', 'centered', 'compact'],
    'card' => ['soft', 'editorial', 'boxed'],
    'grid' => ['airy', 'dense'],
    'footer' => ['rich', 'minimal'],
];

function layoutsStylesheet(): string
{
    return file_get_contents(base_path('packages/Webkul/Shop/src/Resources/assets/css/layouts.css'));
}

function shopView(string $path): string
{
    return file_get_contents(base_path('packages/Webkul/Shop/src/Resources/views/'.$path));
}

it('should give every store theme one known variant of each part of the storefront', function (string $code) {
    $layout = config("themes.shop.{$code}.layout");

    foreach (LAYOUT_VARIANTS as $part => $variants) {
        expect($layout[$part] ?? null)->toBeIn($variants);
    }
})->with(STORE_THEME_CODES);

it('should not change the shape of the default theme', function () {
    expect(config('themes.shop.default.layout'))->toBeNull();
});

it('should differ in shape between store themes and not only in colour', function () {
    $mixes = collect(STORE_THEME_CODES)->map(fn (string $code) => implode('/', config("themes.shop.{$code}.layout")))->unique();

    expect($mixes->count())->toBeGreaterThanOrEqual(5);

    foreach (LAYOUT_VARIANTS as $part => $variants) {
        expect(collect(STORE_THEME_CODES)->map(fn (string $code) => config("themes.shop.{$code}.layout.{$part}"))->unique()->count())->toBeGreaterThan(1);
    }
});

it('should style every variant that is not the markup as it ships', function () {
    $css = layoutsStylesheet();

    foreach (LAYOUT_VARIANTS as $part => $variants) {
        foreach (array_slice($variants, 1) as $variant) {
            expect($css)->toContain("[data-{$part}=\"{$variant}\"]");
        }
    }
});

it('should print the chosen variants on the root element and fall back to the shipped markup', function () {
    $layout = shopView('components/layouts/index.blade.php');

    foreach (array_keys(LAYOUT_VARIANTS) as $part) {
        expect($layout)->toContain("data-{$part}=\"{{ \$layout['{$part}'] ??");
    }
});

it('should carry the hooks the variants style on the core templates', function (string $view, string $slot) {
    expect(shopView($view))->toContain("data-slot=\"{$slot}\"");
})->with([
    ['components/layouts/header/desktop/bottom.blade.php', 'header-bar'],
    ['components/layouts/header/desktop/bottom.blade.php', 'header-nav'],
    ['components/layouts/header/desktop/bottom.blade.php', 'header-tools'],
    ['components/layouts/header/desktop/bottom.blade.php', 'header-links'],
    ['components/layouts/header/desktop/bottom.blade.php', 'logo'],
    ['components/layouts/header/desktop/bottom.blade.php', 'search'],
    ['components/products/card.blade.php', 'card'],
    ['components/products/card.blade.php', 'card-media'],
    ['components/products/card.blade.php', 'card-info'],
    ['categories/view.blade.php', 'product-grid'],
    ['components/layouts/footer/index.blade.php', 'footer'],
]);

it('should use only slots the templates define', function () {
    preg_match_all('/data-slot="([a-z-]+)"/', layoutsStylesheet(), $used);

    $templates = collect([
        'components/layouts/header/desktop/bottom.blade.php',
        'components/products/card.blade.php',
        'categories/view.blade.php',
        'components/layouts/footer/index.blade.php',
    ])->map(fn (string $view) => shopView($view))->implode("\n");

    foreach (array_unique($used[1]) as $slot) {
        expect($templates)->toContain("data-slot=\"{$slot}\"");
    }
});
