<?php

use Illuminate\Support\Facades\DB;
use Webkul\ShopSetup\Checklist\SetupChecklist;
use Webkul\ShopSetup\Support\ConfigValues;
use Webkul\ShopSetup\Tax\TaxSetup;

function checklistItem(string $key): array
{
    return collect(app(SetupChecklist::class)->items())->firstWhere('key', $key);
}

// ============================================================================
// What Is Done
// ============================================================================

it('should list the five things a shop needs before its first sale', function () {
    expect(collect(app(SetupChecklist::class)->items())->pluck('key')->all())->toBe(['tax', 'store', 'shipping', 'email', 'product']);
});

it('should mark tax done once the owner has confirmed a choice', function () {
    expect(checklistItem('tax')['done'])->toBeFalse();

    app(TaxSetup::class)->apply(TaxSetup::MODE_NONE);

    expect(checklistItem('tax')['done'])->toBeTrue();
});

it('should mark the shop address done only with both a street and a city', function () {
    app(ConfigValues::class)->set('sales.shipping.origin.address', '1 Royal Road');

    expect(checklistItem('store')['done'])->toBeFalse();

    app(ConfigValues::class)->set('sales.shipping.origin.city', 'Port Louis');

    expect(checklistItem('store')['done'])->toBeTrue();
});

it('should mark email done once a mail server other than the fallback is set', function (string $host, bool $done) {
    app(ConfigValues::class)->set('emails.configure.smtp.host', $host);

    expect(checklistItem('email')['done'])->toBe($done);
})->with([
    'the fallback address' => ['127.0.0.1', false],
    'localhost' => ['localhost', false],
    'a real server' => ['smtp.example.com', true],
]);

it('should mark shipping done once the owner has saved the shipping settings', function () {
    DB::table('core_config')->where('code', 'like', 'sales.carriers.%')->delete();

    expect(checklistItem('shipping')['done'])->toBeFalse();

    app(ConfigValues::class)->set('sales.carriers.flatrate.default_rate', '5');

    expect(checklistItem('shipping')['done'])->toBeTrue();
});

it('should give every item a title, a hint and a link', function () {
    foreach (app(SetupChecklist::class)->items() as $item) {
        expect($item['title'])->not->toBe("shop_setup::app.checklist.items.{$item['key']}.title")
            ->and($item['hint'])->not->toBeEmpty()
            ->and($item['url'])->toStartWith('http');
    }
});
