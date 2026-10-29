<?php

namespace Webkul\ShopSetup\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Image\ImageException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Admin\Http\Requests\ProductForm;
use Webkul\Attribute\Repositories\AttributeFamilyRepository;
use Webkul\Category\Repositories\CategoryRepository;
use Webkul\Core\Rules\Slug;
use Webkul\Product\Repositories\ProductAttributeValueRepository;
use Webkul\Product\Repositories\ProductRepository;

class SimpleProductController extends Controller
{
    /**
     * Attribute codes the merchant form supplies or generates itself.
     */
    protected const SUPPLIED_ATTRIBUTES = [
        'sku', 'name', 'description', 'short_description', 'url_key', 'price', 'special_price',
        'weight', 'status', 'visible_individually', 'guest_checkout', 'manage_stock',
    ];

    /**
     * Create the merchant product controller.
     */
    public function __construct(
        protected ProductRepository $products,
        protected AttributeFamilyRepository $families,
        protected CategoryRepository $categories,
    ) {}

    /**
     * Show the essential fields for a new simple product.
     */
    public function create(): View
    {
        $ids = $this->categories->getVisibleCategoryIds(core()->getDefaultChannel()->root_category_id);

        $family = $this->families->findOneByField('code', 'default');

        return view('shop_setup::admin.products.create', [
            'unsupported' => $family ? $this->unsupportedRequired($family) : [],
            'categories' => $this->categories->findWhereIn('id', $ids),
        ]);
    }

    /**
     * Create and validate through the existing product rules before saving media or inventory.
     */
    public function store(Request $request): RedirectResponse
    {
        $channel = core()->getDefaultChannel();
        $family = $this->families->findOneByField('code', 'default');
        $source = $channel->inventory_sources->where('status', 1)->sortBy('priority')->first();

        abort_unless($family && $source, 422, trans('shop_setup::merchant.setup-required'));

        $unsupported = $this->unsupportedRequired($family);

        if ($unsupported) {
            return redirect()->route('admin.catalog.products.simple.create')->withErrors([
                'attributes' => trans('shop_setup::merchant.custom-required', ['attributes' => implode(', ', $unsupported)]),
            ]);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:65535'],
            'price' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'special_price' => ['nullable', 'numeric', 'min:0', 'lt:price'],
            'quantity' => ['required', 'integer', 'min:0', 'max:2147483647'],
            'status' => ['required', 'in:0,1'],
            'category_id' => ['nullable', Rule::in($this->categories->getVisibleCategoryIds($channel->root_category_id))],
            'sku' => ['nullable', 'string', 'max:255', 'unique:products,sku', new Slug],
            'weight' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'images' => ['sometimes', 'array'],
            'images.files' => ['sometimes', 'array', 'max:20'],
            'images.files.*' => ['required', 'image', 'mimes:jpg,jpeg,png,webp,bmp', 'max:5120'],
        ]);

        $sku = ($data['sku'] ?? null) ?: 'product-'.Str::lower((string) Str::ulid());

        $createdId = null;

        try {
            $product = DB::transaction(function () use ($request, $data, $sku, $channel, $family, $source, &$createdId) {
                Event::dispatch('catalog.product.create.before');

                $product = $this->products->create(['type' => 'simple', 'attribute_family_id' => $family->id, 'sku' => $sku]);
                $createdId = $product->id;

                $values = array_replace($data, [
                    'id' => $product->id,
                    'sku' => $sku,
                    'url_key' => (Str::slug(Str::limit($data['name'], 150, '')) ?: 'product').'-'.Str::lower((string) Str::ulid()),
                    'short_description' => $data['description'],
                    'weight' => filled($data['weight'] ?? null) ? $data['weight'] : 0,
                    'manage_stock' => 1,
                    'visible_individually' => 1,
                    'guest_checkout' => 1,
                    'channel' => $channel->code,
                    'locale' => $channel->default_locale->code,
                    'channels' => [$channel->id],
                    'inventories' => [$source->id => $data['quantity']],
                    'categories' => empty($data['category_id']) ? [] : [$data['category_id']],
                ]);

                $request->merge($values);

                $form = ProductForm::createFrom($request, new ProductForm($this->products, app(ProductAttributeValueRepository::class)));

                Validator::make($values, $form->rules(), $form->messages(), $form->attributes())->validate();

                Event::dispatch('catalog.product.create.after', $product);
                Event::dispatch('catalog.product.update.before', $product->id);

                $product = $this->products->update($values, $product->id);

                Event::dispatch('catalog.product.update.after', $product);

                return $product;
            });
        } catch (Throwable $exception) {
            if ($createdId) {
                Storage::deleteDirectory('products/'.$createdId);
            }

            if ($exception instanceof ImageException) {
                throw ValidationException::withMessages(['images.files' => trans('shop_setup::merchant.invalid-image')]);
            }

            throw $exception;
        }

        session()->flash('success', trans('shop_setup::merchant.product-saved'));

        return redirect()->route('admin.catalog.products.edit', $product->id);
    }

    /**
     * Name the required family attributes this form cannot fill, which need the full product form.
     *
     * @return array<int, string>
     */
    protected function unsupportedRequired($family): array
    {
        return $family->custom_attributes
            ->where('is_required', 1)
            ->reject(fn ($attribute) => in_array($attribute->code, self::SUPPLIED_ATTRIBUTES, true))
            ->map(fn ($attribute) => $attribute->admin_name ?: $attribute->code)
            ->values()
            ->all();
    }
}
