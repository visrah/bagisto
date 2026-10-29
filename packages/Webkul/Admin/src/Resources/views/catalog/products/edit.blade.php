@php $merchantMode = config('shop_setup.admin_mode') !== 'full'; @endphp

<x-admin::layouts>
    <x-slot:title>
        @lang('admin::app.catalog.products.edit.title')
    </x-slot>

    {!! view_render_event('bagisto.admin.catalog.product.edit.before', ['product' => $product]) !!}

    <x-admin::form
        method="PUT"
        enctype="multipart/form-data"
    >
        {!! view_render_event('bagisto.admin.catalog.product.edit.actions.before', ['product' => $product]) !!}

        <div class="grid gap-2.5">
            <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
                <div class="grid gap-1.5">
                    <p class="text-xl font-bold leading-6 text-gray-800 dark:text-white">
                        @lang('admin::app.catalog.products.edit.title')
                    </p>
                </div>

                <div class="flex items-center gap-x-2.5">
                    <a
                        href="{{ route('admin.catalog.products.index') }}"
                        class="transparent-button hover:bg-gray-200 dark:text-white dark:hover:bg-gray-800"
                    >
                        @lang('admin::app.account.edit.back-btn')
                    </a>

                    @if (
                        $product->status
                        && $product->visible_individually
                        && $product->url_key
                    )
                        <a
                            href="{{ route('shop.product_or_category.index', $product->url_key) }}"
                            class="secondary-button"
                            target="_blank"
                        >
                            @lang('admin::app.catalog.products.edit.preview')
                        </a>
                    @endif

                    <button class="primary-button">
                        @lang('admin::app.catalog.products.edit.save-btn')
                    </button>
                </div>
            </div>
        </div>

        @php
            $channels = core()->getAllChannels();

            $currentChannel = core()->getRequestedChannel();

            $currentLocale = core()->getAllLocales()->firstWhere('code', $currentChannel->resolveLocaleCode(core()->getRequestedLocaleCode()));
        @endphp

        <div class="mt-7 flex items-center justify-between gap-4 max-md:flex-wrap">
            <div class="flex items-center gap-x-1">
                <x-admin::dropdown :class="$channels->count() <= 1 ? 'hidden' : ''">
                    <x-slot:toggle>
                        <button
                            type="button"
                            class="transparent-button px-1 py-1.5 hover:bg-gray-200 focus:bg-gray-200 dark:text-white dark:hover:bg-gray-800 dark:focus:bg-gray-800"
                        >
                            <span class="icon-store text-2xl"></span>

                            <span v-pre>{{ $currentChannel->name }}</span>

                            <input
                                type="hidden"
                                name="channel"
                                value="{{ $currentChannel->code }}"
                            />

                            <span class="icon-sort-down text-2xl"></span>
                        </button>
                    </x-slot>

                    <x-slot:content class="p-0!">
                        @foreach ($channels as $channel)
                            <a
                                href="?{{ Arr::query(['channel' => $channel->code, 'locale' => $channel->default_locale?->code ?? $currentLocale->code ]) }}"
                                class="flex cursor-pointer gap-2.5 px-5 py-2 text-base hover:bg-gray-100 dark:text-white dark:hover:bg-gray-950"
                                v-pre
                            >
                                {{ $channel->name }}
                            </a>
                        @endforeach
                    </x-slot>
                </x-admin::dropdown>

                <x-admin::dropdown :class="$currentChannel->locales->count() <= 1 ? 'hidden' : ''">
                    <x-slot:toggle>
                        <button
                            type="button"
                            class="transparent-button px-1 py-1.5 hover:bg-gray-200 focus:bg-gray-200 dark:text-white dark:hover:bg-gray-800 dark:focus:bg-gray-800"
                        >
                            <span class="icon-language text-2xl"></span>

                            <span v-pre>{{ $currentLocale->name }}</span>

                            <input
                                type="hidden"
                                name="locale"
                                value="{{ $currentLocale->code }}"
                            />

                            <span class="icon-sort-down text-2xl"></span>
                        </button>
                    </x-slot>

                    <x-slot:content class="p-0!">
                        @foreach ($currentChannel->locales->sortBy('name') as $locale)
                            <a
                                href="?{{ Arr::query(['channel' => $currentChannel->code, 'locale' => $locale->code]) }}"
                                class="flex gap-2.5 px-5 py-2 text-base cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-950 dark:text-white {{ $locale->code == $currentLocale->code ? 'bg-gray-100 dark:bg-gray-950' : ''}}"
                                v-pre
                            >
                                {{ $locale->name }}
                            </a>
                        @endforeach
                    </x-slot>
                </x-admin::dropdown>
            </div>
        </div>

        {!! view_render_event('bagisto.admin.catalog.product.edit.actions.after', ['product' => $product]) !!}

        {!! view_render_event('bagisto.admin.catalog.product.edit.form.before', ['product' => $product]) !!}

        <div class="mt-3.5 flex gap-2.5 max-xl:flex-wrap">
            @php
                $groupedColumns = $product->attribute_family->attribute_groups->groupBy('column');

                $isSingleColumn = $groupedColumns->count() !== 2;
            @endphp

            @foreach ($groupedColumns as $column => $groups)
                {!! view_render_event("bagisto.admin.catalog.product.edit.form.column_{$column}.before", ['product' => $product]) !!}

                <div class="flex flex-col gap-2 {{ $column == 1 ? 'flex-1 max-xl:flex-auto' : 'w-90 max-w-full max-sm:w-full' }}">
                    @foreach ($groups as $group)
                        @php $customAttributes = $product->getEditableAttributes($group); @endphp

                        @if (
                            $group->code === 'inventories'
                            && (
                                $product->getTypeInstance()->isComposite()
                                || $product->type === 'downloadable'
                            )
                        )
                            @continue
                        @endif

                        @if ($group->code === 'rma')
                            @if (
                                ! in_array($product->type, explode(',', core()->getConfigData('sales.rma.setting.select_allowed_product_type')))
                                && (
                                    $product->type != 'simple'
                                    && empty($product->parent_id)
                                )
                            )
                                @continue
                            @endif
                        @endif

                        @if ($customAttributes->isNotEmpty())
                            {!! view_render_event("bagisto.admin.catalog.product.edit.form.{$group->code}.before", ['product' => $product]) !!}

                            @php $advancedGroup = $merchantMode && in_array($group->code, ['meta_description', 'shipping', 'settings', 'rma']); @endphp

                            @if ($advancedGroup)
                                <details @if ($errors->any()) open @endif>
                                    <summary class="cursor-pointer rounded-sm border border-gray-200 p-4 font-semibold dark:border-gray-800 text-gray-900 dark:text-white">{{ $group->name }}</summary>
                            @endif

                            <div class="box-shadow relative rounded-sm bg-white p-4 dark:bg-gray-900">
                                <p
                                    class="mb-4 text-base font-semibold text-gray-800 dark:text-white"
                                    v-pre
                                >
                                    {{ $group->name }}
                                </p>

                                @if ($group->code == 'meta_description')
                                    <x-admin::seo />
                                @endif

                                @foreach ($customAttributes as $attribute)
                                    {!! view_render_event("bagisto.admin.catalog.product.edit.form.{$group->code}.controls.before", ['product' => $product]) !!}

                                    @php $advancedAttribute = $merchantMode && ! $advancedGroup && ! in_array($attribute->code, ['name', 'description', 'price', 'special_price', 'status', 'manage_stock']); @endphp

                                    @if ($advancedAttribute)
                                        <details @if ($errors->has($attribute->code)) open @endif class="mb-3">
                                            <summary class="cursor-pointer py-2 text-sm font-medium text-gray-900 dark:text-white">{{ $attribute->admin_name }}</summary>
                                    @endif

                                    <x-admin::form.control-group class="last:mb-0!">
                                        <x-admin::form.control-group.label>
                                            {!! e($attribute->admin_name) . ($attribute->is_required ? '<span class="required"></span>' : '') !!}

                                            @if (
                                                $attribute->value_per_channel
                                                && $channels->count() > 1
                                            )
                                                <span
                                                    class="rounded-sm border border-gray-200 bg-gray-100 px-1 py-0.5 text-[10px] font-semibold leading-normal text-gray-600"
                                                    v-pre
                                                >
                                                    {{ $currentChannel->name }}
                                                </span>
                                            @endif

                                            @if ($attribute->value_per_locale && $currentChannel->locales->count() > 1)
                                                <span
                                                    class="rounded-sm border border-gray-200 bg-gray-100 px-1 py-0.5 text-[10px] font-semibold leading-normal text-gray-600"
                                                    v-pre
                                                >
                                                    {{ $currentLocale->name }}
                                                </span>
                                            @endif
                                        </x-admin::form.control-group.label>

                                        @include ('admin::catalog.products.edit.controls', [
                                            'attribute' => $attribute,
                                            'product'   => $product,
                                        ])

                                        <x-admin::form.control-group.error :control-name="$attribute->code . (in_array($attribute->type, ['multiselect', 'checkbox']) ? '[]' : '')" />
                                    </x-admin::form.control-group>

                                    @if ($advancedAttribute)
                                        </details>
                                    @endif

                                    {!! view_render_event("bagisto.admin.catalog.product.edit.form.{$group->code}.controls.after", ['product' => $product]) !!}
                                @endforeach

                                @if ($group->code === 'price')
                                    <details @if (! $merchantMode || $errors->any()) open @endif>
                                        <summary class="cursor-pointer py-3 font-medium text-gray-900 dark:text-white">@lang('shop_setup::merchant.advanced')</summary>
                                        @include('admin::catalog.products.edit.price.group')
                                    </details>
                                @endif

                                @includeWhen($group->code === 'inventories', 'admin::catalog.products.edit.inventories')
                            </div>

                            @if ($advancedGroup)
                                </details>
                            @endif

                            {!! view_render_event("bagisto.admin.catalog.product.edit.form.{$group->code}.after", ['product' => $product]) !!}
                        @endif
                    @endforeach

                    @if ($column == 1)
                        @include('admin::catalog.products.edit.images')

                        <details @if (! $merchantMode || $errors->any()) open @endif>
                            <summary class="cursor-pointer rounded-sm border border-gray-200 p-3 font-medium dark:border-gray-800 text-gray-900 dark:text-white">{{ trans('admin::app.catalog.products.edit.videos.title') }}</summary>
                            @include('admin::catalog.products.edit.videos')
                        </details>

                        @if ($merchantMode && $product->type === 'simple')
                            <details @if ($errors->any()) open @endif>
                                <summary class="cursor-pointer rounded-sm border border-gray-200 p-3 font-medium dark:border-gray-800 text-gray-900 dark:text-white">@lang('shop_setup::merchant.advanced')</summary>
                                @includeIf('admin::catalog.products.edit.types.' . $product->type)
                            </details>
                        @else
                            @includeIf('admin::catalog.products.edit.types.' . $product->type)
                        @endif

                        <details @if (! $merchantMode || $errors->any()) open @endif>
                            <summary class="cursor-pointer rounded-sm border border-gray-200 p-3 font-medium dark:border-gray-800 text-gray-900 dark:text-white">{{ trans('shop_setup::merchant.advanced') }}</summary>
                            @include('admin::catalog.products.edit.links')
                        </details>

                        @foreach ($product->getTypeInstance()->getAdditionalViews() as $view)
                            @includeIf($view)
                        @endforeach
                    @elseif (! $isSingleColumn)
                        @include('admin::catalog.products.edit.channels')

                        @include('admin::catalog.products.edit.categories')
                    @endif
                </div>

                @if (
                    $isSingleColumn
                    && (
                        $column == 1
                        || $column == 2
                    )
                )
                    <div class="w-90 max-w-full max-sm:w-full">
                        @if ($column == 2)
                            @include('admin::catalog.products.edit.images')

                            <details @if (! $merchantMode || $errors->any()) open @endif>
                            <summary class="cursor-pointer rounded-sm border border-gray-200 p-3 font-medium dark:border-gray-800 text-gray-900 dark:text-white">{{ trans('admin::app.catalog.products.edit.videos.title') }}</summary>
                            @include('admin::catalog.products.edit.videos')
                        </details>

                            @if ($merchantMode && $product->type === 'simple')
                            <details @if ($errors->any()) open @endif>
                                <summary class="cursor-pointer rounded-sm border border-gray-200 p-3 font-medium dark:border-gray-800 text-gray-900 dark:text-white">@lang('shop_setup::merchant.advanced')</summary>
                                @includeIf('admin::catalog.products.edit.types.' . $product->type)
                            </details>
                        @else
                            @includeIf('admin::catalog.products.edit.types.' . $product->type)
                        @endif

                            <details @if (! $merchantMode || $errors->any()) open @endif>
                            <summary class="cursor-pointer rounded-sm border border-gray-200 p-3 font-medium dark:border-gray-800 text-gray-900 dark:text-white">{{ trans('shop_setup::merchant.advanced') }}</summary>
                            @include('admin::catalog.products.edit.links')
                        </details>

                            @foreach ($product->getTypeInstance()->getAdditionalViews() as $view)
                                @includeIf($view)
                            @endforeach
                        @endif

                        @include('admin::catalog.products.edit.channels')

                        @include('admin::catalog.products.edit.categories')
                    </div>
                @endif

                {!! view_render_event("bagisto.admin.catalog.product.edit.form.column_{$column}.after", ['product' => $product]) !!}
            @endforeach
        </div>

        {!! view_render_event('bagisto.admin.catalog.product.edit.form.after', ['product' => $product]) !!}
    </x-admin::form>

    {!! view_render_event('bagisto.admin.catalog.product.edit.after', ['product' => $product]) !!}
</x-admin::layouts>
