<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Webkul\Attribute\Models\Attribute;
use Webkul\Product\Models\Product;

use function Pest\Laravel\get;
use function Pest\Laravel\post;

// ============================================================================
// Simple Product Creation
// ============================================================================

it('creates a ready to sell product without technical selections', function () {
    Storage::fake();
    $this->loginAsAdmin();

    $response = post(route('admin.catalog.products.simple.store'), [
        'name' => 'Local tea',
        'description' => 'Loose leaf tea.',
        'price' => '125.50',
        'quantity' => 12,
        'status' => 1,
        'weight' => '',
        'category_id' => core()->getDefaultChannel()->root_category_id,
        'images' => ['files' => ['image_0' => UploadedFile::fake()->image('tea.jpg')]],
    ]);

    $product = Product::query()->where('type', 'simple')->latest('id')->firstOrFail();

    $response->assertRedirect(route('admin.catalog.products.edit', $product->id));

    expect($product->name)->toBe('Local tea')
        ->and((float) $product->price)->toBe(125.5)
        ->and($product->sku)->not->toBeEmpty()
        ->and($product->url_key)->toStartWith('local-tea-')
        ->and($product->inventories->sum('qty'))->toBe(12)
        ->and($product->images)->toHaveCount(1)
        ->and($product->categories->pluck('id')->all())->toContain(core()->getDefaultChannel()->root_category_id)
        ->and($product->channels->pluck('id')->all())->toBe([core()->getDefaultChannel()->id]);

    Storage::assertExists($product->images->first()->path);
});

it('generates different identifiers for products with identical names', function () {
    $this->loginAsAdmin();

    foreach (range(1, 2) as $attempt) {
        post(route('admin.catalog.products.simple.store'), [
            'name' => 'Tea', 'description' => 'Tea.', 'price' => 100, 'quantity' => 0, 'status' => 0,
        ])->assertSessionHasNoErrors();
    }

    $products = Product::query()->latest('id')->limit(2)->get();

    expect($products->pluck('sku')->unique())->toHaveCount(2)
        ->and($products->map->url_key->unique())->toHaveCount(2);
});

it('validates merchant input before creating a product', function (array $input, string $field) {
    $this->loginAsAdmin();
    $before = Product::query()->count();

    post(route('admin.catalog.products.simple.store'), array_replace([
        'name' => 'Tea', 'description' => 'Tea.', 'price' => 100, 'quantity' => 5, 'status' => 1,
    ], $input))->assertSessionHasErrors($field);

    expect(Product::query()->count())->toBe($before);
})->with([
    'negative price' => [['price' => -1], 'price'],
    'fractional stock' => [['quantity' => 1.5], 'quantity'],
    'invalid status' => [['status' => 9], 'status'],
    'invalid category' => [['category_id' => 9999999], 'category_id'],
    'sale above price' => [['special_price' => 101], 'special_price'],
    'script upload' => [['images' => ['files' => [UploadedFile::fake()->create('bad.php', 1, 'text/plain')]]], 'images.files.0'],
]);

it('requires product creation permission', function () {
    $this->loginAsAdminWithPermissions(['dashboard']);

    get(route('admin.catalog.products.simple.create'))->assertUnauthorized();
    post(route('admin.catalog.products.simple.store'), [])->assertUnauthorized();
});

it('rejects an image with a valid header but corrupt contents without leaving a product', function () {
    Storage::fake();
    $this->loginAsAdmin();
    $count = Product::query()->count();
    $image = UploadedFile::fake()->createWithContent('corrupt.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aB1cAAAAASUVORK5CYII='));

    post(route('admin.catalog.products.simple.store'), [
        'name' => 'Tea', 'description' => 'Tea.', 'price' => 100, 'quantity' => 1, 'status' => 1,
        'images' => ['files' => [$image]],
    ])->assertSessionHasErrors('images.files');

    expect(Product::query()->count())->toBe($count)
        ->and(Storage::allFiles('products'))->toBeEmpty();
});

it('sends the merchant to the full form when the family needs details the quick form lacks', function () {
    Storage::fake();
    $this->loginAsAdmin();
    $count = Product::query()->count();

    $attribute = Attribute::query()->where('code', 'color')->firstOrFail();
    $attribute->update(['is_required' => 1]);

    get(route('admin.catalog.products.simple.create'))->assertSee($attribute->admin_name);

    post(route('admin.catalog.products.simple.store'), [
        'name' => 'Tea', 'description' => 'Tea.', 'price' => 100, 'quantity' => 1, 'status' => 1,
    ])->assertRedirect(route('admin.catalog.products.simple.create'))->assertSessionHasErrors('attributes');

    expect(Product::query()->count())->toBe($count);
});

// ============================================================================
// Product Edit Preservation
// ============================================================================

it('keeps every field of the full product form when the advanced sections are collapsed', function (string $type) {
    $this->loginAsAdmin();

    $product = Product::factory()->create(['type' => $type]);

    $names = function (string $mode) use ($product) {
        config(['shop_setup.admin_mode' => $mode]);

        preg_match_all('/\sname="([^"]+)"/', get(route('admin.catalog.products.edit', $product->id))->assertOk()->getContent(), $matches);

        return array_unique($matches[1]);
    };

    $full = $names('full');

    expect($full)->not->toBeEmpty()
        ->and(array_diff($full, $names('simple')))->toBeEmpty();
})->with(['simple', 'virtual', 'configurable', 'grouped', 'bundle', 'downloadable']);

it('renders group prices and the relation editors in the collapsed form so a save does not drop them', function () {
    $this->loginAsAdmin();

    config(['shop_setup.admin_mode' => 'simple']);

    $product = Product::factory()->create(['type' => 'simple']);
    $related = Product::factory()->create(['type' => 'simple']);
    $categoryId = core()->getDefaultChannel()->root_category_id;

    $product->categories()->sync([$categoryId]);
    $product->related_products()->sync([$related->id]);
    $product->customer_group_prices()->create(['qty' => 5, 'value_type' => 'fixed', 'value' => 77.25, 'customer_group_id' => null]);

    $html = get(route('admin.catalog.products.edit', $product->id))->assertOk()->getContent();

    expect($html)->toContain('77.25')
        ->and($html)->toContain('related_products')
        ->and($html)->toContain('categories');
});

it('renders stored custom options in the collapsed simple form', function () {
    $this->loginAsAdmin();

    config(['shop_setup.admin_mode' => 'simple']);

    $product = Product::factory()->create(['type' => 'simple']);
    $product->customizable_options()->create(['type' => 'text', 'is_required' => 1, 'max_characters' => 20, 'sort_order' => 1, 'label' => 'Engraving text']);

    get(route('admin.catalog.products.edit', $product->id))->assertOk()->assertSee('Engraving text');
});
