<?php

namespace Webkul\ShopSetup\Checklist;

use Illuminate\Support\Facades\DB;
use Webkul\ShopSetup\Support\ConfigValues;
use Webkul\ShopSetup\Tax\TaxSetup;

class SetupChecklist
{
    /**
     * Hosts that mean no mail server has been set up: the address the mailer falls back to.
     *
     * @var array<int, string>
     */
    protected const array UNSET_MAIL_HOSTS = ['', '127.0.0.1', 'localhost'];

    /**
     * Create a new setup checklist.
     */
    public function __construct(
        protected TaxSetup $taxSetup,
        protected ConfigValues $config,
    ) {}

    /**
     * The things a shop needs before its first sale, each with whether it is done and where to do it.
     *
     * @return array<int, array{key: string, done: bool, title: string, hint: string, url: string}>
     */
    public function items(): array
    {
        $done = [
            'tax' => $this->taxSetup->current()['confirmed'],
            'store' => $this->hasStoreAddress(),
            'shipping' => DB::table('core_config')->where('code', 'like', 'sales.carriers.%')->exists(),
            'email' => $this->hasMailServer(),
            'product' => DB::table('products')->exists(),
        ];

        $urls = [
            'tax' => route('admin.settings.shop_tax.index'),
            'store' => route('admin.configuration.index', ['slug' => 'sales', 'slug2' => 'shipping']),
            'shipping' => route('admin.configuration.index', ['slug' => 'sales', 'slug2' => 'carriers']),
            'email' => route('admin.configuration.index', ['slug' => 'emails', 'slug2' => 'configure']),
            'product' => route('admin.catalog.products.index'),
        ];

        return array_map(fn (string $key) => [
            'key' => $key,
            'done' => $done[$key],
            'title' => trans("shop_setup::app.checklist.items.{$key}.title"),
            'hint' => trans("shop_setup::app.checklist.items.{$key}.hint"),
            'url' => $urls[$key],
        ], array_keys($done));
    }

    /**
     * Whether every item is done.
     */
    public function isComplete(): bool
    {
        return collect($this->items())->every(fn (array $item) => $item['done']);
    }

    /**
     * Whether the shop's origin address has been filled in: the street and the city.
     */
    protected function hasStoreAddress(): bool
    {
        return filled($this->config->get('sales.shipping.origin.address'))
            && filled($this->config->get('sales.shipping.origin.city'));
    }

    /**
     * Whether a mail server other than the fallback has been set.
     */
    protected function hasMailServer(): bool
    {
        return ! in_array(trim((string) $this->config->get('emails.configure.smtp.host')), self::UNSET_MAIL_HOSTS, true);
    }
}
