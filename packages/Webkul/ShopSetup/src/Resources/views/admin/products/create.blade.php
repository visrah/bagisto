<x-admin::layouts>
    <x-slot:title>@lang('shop_setup::merchant.add-product')</x-slot>

    <x-admin::form :action="route('admin.catalog.products.simple.store')" enctype="multipart/form-data">
        <div class="mb-5 flex flex-wrap items-center justify-between gap-4">
            <h1 class="text-xl font-bold text-gray-800 dark:text-white">@lang('shop_setup::merchant.add-product')</h1>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.catalog.products.index') }}" class="transparent-button dark:text-white">@lang('admin::app.account.edit.back-btn')</a>
                <button class="primary-button">@lang('shop_setup::merchant.create-product')</button>
            </div>
        </div>

        @if ($unsupported && ! $errors->has('attributes'))
            <div role="alert" class="mb-4 rounded-sm border border-yellow-400 p-4 text-yellow-800 dark:text-yellow-300">
                {{ trans('shop_setup::merchant.custom-required', ['attributes' => implode(', ', $unsupported)]) }}
            </div>
        @endif

        @if ($errors->any())
            <div role="alert" class="mb-4 rounded-sm border border-red-300 p-4 text-red-700 dark:text-red-300">
                <p class="font-semibold">@lang('shop_setup::merchant.error-summary')</p>
                <ul class="mt-2 list-inside list-disc">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid items-start gap-4 xl:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
            <div class="min-w-0 rounded-sm border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                <h2 class="mb-4 text-base font-semibold text-gray-900 dark:text-white">@lang('shop_setup::merchant.basic-information')</h2>

                @foreach (['name' => 'product-name', 'description' => 'product-description', 'price' => 'price', 'special_price' => 'sale-price'] as $field => $label)
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label :for="$field">{{ trans('shop_setup::merchant.'.$label) }}</x-admin::form.control-group.label>
                        <x-admin::form.control-group.control :type="$field === 'description' ? 'textarea' : (in_array($field, ['price', 'special_price']) ? 'price' : 'text')" :id="$field" :name="$field" :value="old($field)" :label="trans('shop_setup::merchant.'.$label)" :rules="$field === 'special_price' ? 'decimal|min_value:0' : (in_array($field, ['price']) ? 'required|decimal|min_value:0' : 'required')" />
                        <x-admin::form.control-group.error :control-name="$field" />
                    </x-admin::form.control-group>
                @endforeach

                <p class="mb-4 text-sm text-gray-600 dark:text-gray-300">{{ trans('shop_setup::merchant.currency-hint', ['currency' => core()->getBaseCurrencyCode()]) }}</p>

                <x-admin::form.control-group>
                    <x-admin::form.control-group.label for="product-images">@lang('shop_setup::merchant.images')</x-admin::form.control-group.label>
                    <input id="product-images" type="file" name="images[files][]" accept="image/jpeg,image/png,image/webp,image/bmp" multiple class="block w-full text-sm text-gray-900 dark:text-white">
                    <x-admin::form.control-group.error control-name="images.files" />
                </x-admin::form.control-group>
            </div>

            <div class="min-w-0 rounded-sm border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                <h2 class="mb-4 text-base font-semibold text-gray-900 dark:text-white">@lang('shop_setup::merchant.inventory')</h2>
                <x-admin::form.control-group>
                    <x-admin::form.control-group.label for="quantity">@lang('shop_setup::merchant.stock')</x-admin::form.control-group.label>
                    <x-admin::form.control-group.control type="number" id="quantity" name="quantity" min="0" step="1" rules="required|integer|min_value:0" :value="old('quantity', 0)" :label="trans('shop_setup::merchant.stock')" />
                    <x-admin::form.control-group.error control-name="quantity" />
                </x-admin::form.control-group>

                <h2 class="mb-4 text-base font-semibold text-gray-900 dark:text-white">@lang('shop_setup::merchant.organization')</h2>
                <x-admin::form.control-group>
                    <x-admin::form.control-group.label for="category_id">@lang('shop_setup::merchant.category')</x-admin::form.control-group.label>
                    <x-admin::form.control-group.control type="select" id="category_id" name="category_id" :value="old('category_id', '')">
                        <option value="">@lang('shop_setup::merchant.no-category')</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </x-admin::form.control-group.control>
                    <x-admin::form.control-group.error control-name="category_id" />
                </x-admin::form.control-group>
                <x-admin::form.control-group>
                    <x-admin::form.control-group.label for="status">@lang('shop_setup::merchant.status')</x-admin::form.control-group.label>
                    <x-admin::form.control-group.control type="select" id="status" name="status" :value="old('status', 1)">
                        <option value="1">@lang('shop_setup::merchant.published')</option>
                        <option value="0">@lang('shop_setup::merchant.draft')</option>
                    </x-admin::form.control-group.control>
                </x-admin::form.control-group>

                <details @if ($errors->has('sku') || $errors->has('weight')) open @endif class="border-t border-gray-200 pt-4 dark:border-gray-800">
                    <summary class="cursor-pointer py-2 font-semibold text-gray-900 dark:text-white">@lang('shop_setup::merchant.advanced')</summary>
                    @foreach (['sku', 'weight'] as $field)
                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label :for="$field">{{ trans('shop_setup::merchant.'.$field) }}</x-admin::form.control-group.label>
                            <x-admin::form.control-group.control type="text" :id="$field" :name="$field" :value="old($field)" :label="trans('shop_setup::merchant.'.$field)" />
                            <x-admin::form.control-group.error :control-name="$field" />
                        </x-admin::form.control-group>
                    @endforeach
                </details>
            </div>
        </div>
    </x-admin::form>
</x-admin::layouts>
