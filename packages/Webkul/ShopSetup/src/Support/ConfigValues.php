<?php

namespace Webkul\ShopSetup\Support;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Webkul\Core\Helpers\CacheGeneration;
use Webkul\Core\Repositories\CoreConfigRepository;

class ConfigValues
{
    /**
     * Read a stored configuration value from the row the application itself would read.
     */
    public function get(string $code): ?string
    {
        $value = $this->row($code)->value('value');

        return $value === null ? null : (string) $value;
    }

    /**
     * Store a configuration value where the application will read it, replacing an existing one.
     *
     * A field bound to a channel or a locale is only read from the row of the default channel or locale, so a row
     * written for all of them would be silently ignored. The configuration repository caches its reads and only forgets
     * them when a write goes through it, so the write here moves it on to a new cache generation itself.
     */
    public function set(string $code, string $value): void
    {
        $row = $this->row($code);

        if ($row->exists()) {
            $row->update(['value' => $value, 'updated_at' => now()]);
        } else {
            DB::table('core_config')->insert([
                'code' => $code,
                'value' => $value,
                'channel_code' => $this->channelCode($code),
                'locale_code' => $this->localeCode($code),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        CacheGeneration::bump(CoreConfigRepository::class);
    }

    /**
     * Store a configuration value only when nothing is stored yet, so a value the owner has set is never overwritten.
     */
    public function default(string $code, string $value): void
    {
        if (! $this->row($code)->exists()) {
            $this->set($code, $value);
        }
    }

    /**
     * The query for the one row that holds a configuration value.
     */
    protected function row(string $code): Builder
    {
        $row = DB::table('core_config')->where('code', $code);

        $channel = $this->channelCode($code);

        $locale = $this->localeCode($code);

        $channel === null ? $row->whereNull('channel_code') : $row->where('channel_code', $channel);

        $locale === null ? $row->whereNull('locale_code') : $row->where('locale_code', $locale);

        return $row;
    }

    /**
     * The channel a value is stored for, or null when the field does not depend on the channel.
     */
    protected function channelCode(string $code): ?string
    {
        return empty(core()->getConfigField($code)['channel_based'])
            ? null
            : core()->getDefaultChannelCode();
    }

    /**
     * The locale a value is stored for, or null when the field does not depend on the locale.
     */
    protected function localeCode(string $code): ?string
    {
        return empty(core()->getConfigField($code)['locale_based'])
            ? null
            : core()->getDefaultLocaleCodeFromDefaultChannel();
    }
}
