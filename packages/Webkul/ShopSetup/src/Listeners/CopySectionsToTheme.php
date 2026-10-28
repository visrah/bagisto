<?php

namespace Webkul\ShopSetup\Listeners;

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
    public function __construct(protected SectionRepository $sections) {}

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

        if (
            blank($theme)
            || $theme === self::SOURCE_THEME
            || $this->sections->findWhere(['channel_id' => $channel->id, 'theme_code' => $theme])->isNotEmpty()
        ) {
            return;
        }

        foreach ($this->sections->findWhere(['channel_id' => $channel->id, 'theme_code' => self::SOURCE_THEME]) as $section) {
            $copy = $section->replicateWithTranslations();

            $copy->theme_code = $theme;

            $copy->save();
        }
    }
}
