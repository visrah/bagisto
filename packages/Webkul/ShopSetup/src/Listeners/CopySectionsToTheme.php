<?php

namespace Webkul\ShopSetup\Listeners;

use Webkul\Category\Models\Category;
use Webkul\ShopSetup\Themes\ThemeHeroes;
use Webkul\Theme\Enums\SectionTypeEnum;
use Webkul\Theme\Repositories\SectionRepository;

class CopySectionsToTheme
{
    /**
     * Code of the theme whose homepage sections new themes start from.
     */
    protected const string SOURCE_THEME = 'default';

    /**
     * Create a new listener instance.
     */
    public function __construct(protected SectionRepository $sections, protected ThemeHeroes $heroes) {}

    /**
     * Give a channel's newly chosen theme the homepage it was showing, because sections belong to one theme and
     * would otherwise leave the new theme with an empty storefront.
     *
     * A theme that already has sections of its own is left alone, so switching back and forth never overwrites an edit.
     *
     * @param  object  $channel
     */
    public function handle($channel): void
    {
        $theme = $channel->theme;

        if (blank($theme) || $theme === self::SOURCE_THEME) {
            return;
        }

        $existing = $this->sections->findWhere(['channel_id' => $channel->id, 'theme_code' => $theme]);

        if ($existing->isNotEmpty()) {
            $this->upgradeUntouchedBanner($channel, $theme, $existing);

            return;
        }

        $hero = $this->heroes->for($theme, $this->shopUrl($channel));

        foreach ($this->sections->findWhere(['channel_id' => $channel->id, 'theme_code' => self::SOURCE_THEME]) as $section) {
            $copy = $section->replicateWithTranslations();

            $copy->theme_code = $theme;

            $isHero = $hero !== null && $section->type === SectionTypeEnum::IMAGE_CAROUSEL->value;

            if ($isHero) {
                $copy->type = SectionTypeEnum::STATIC_CONTENT->value;
            }

            $copy->save();

            if ($isHero) {
                $hero = $this->replaceWithHero($copy, $hero);
            }
        }
    }

    /**
     * Give a theme whose homepage was copied before it had a hero of its own that hero, but only while the banner is still the demo
     * one, so nothing the owner changed is ever replaced.
     *
     * @param  iterable<object>  $existing
     */
    protected function upgradeUntouchedBanner(object $channel, string $theme, iterable $existing): void
    {
        $hero = $this->heroes->for($theme, $this->shopUrl($channel));

        $demo = $this->sections->findWhere(['channel_id' => $channel->id, 'theme_code' => self::SOURCE_THEME, 'type' => SectionTypeEnum::IMAGE_CAROUSEL->value])->first();

        if ($hero === null || $demo === null) {
            return;
        }

        foreach ($existing as $section) {
            if ($section->type === SectionTypeEnum::IMAGE_CAROUSEL->value && $section->translations->pluck('options', 'locale')->all() == $demo->translations->pluck('options', 'locale')->all()) {
                $section->type = SectionTypeEnum::STATIC_CONTENT->value;

                $section->save();

                $this->replaceWithHero($section, $hero);

                return;
            }
        }
    }

    /**
     * Put the theme's own hero in place of the demo banner a copied carousel holds, in every language of the section.
     *
     * The first carousel is the banner; a later one is left as it was, so the hero is only ever swapped in once.
     *
     * @param  array{html: string, css: string}  $hero
     */
    protected function replaceWithHero(object $section, array $hero): ?array
    {
        foreach ($section->translations as $translation) {
            $translation->update(['options' => $hero, 'draft_options' => null]);
        }

        return null;
    }

    /**
     * Where the hero's button leads: the shop's first category, or the search page while there are none.
     */
    protected function shopUrl(object $channel): string
    {
        $slug = Category::where('parent_id', $channel->root_category_id)->where('status', 1)->orderBy('position')->first()?->slug;

        return $slug ? url($slug) : route('shop.search.index');
    }
}
