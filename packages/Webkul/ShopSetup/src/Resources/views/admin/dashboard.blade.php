@inject('orderLabels', \Webkul\ShopSetup\Sales\OrderLabels::class)

<x-admin::layouts>
    <x-slot:title>@lang('shop_setup::merchant.dashboard')</x-slot>

    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-xl font-bold text-gray-800 dark:text-white">@lang('shop_setup::merchant.dashboard')</h1>
        @if (bouncer()->hasPermission('catalog.products.create'))
            <a href="{{ route('admin.catalog.products.simple.create') }}" class="primary-button">@lang('shop_setup::merchant.add-product')</a>
        @endif
    </div>

    {!! view_render_event('bagisto.admin.dashboard.overall_details.before') !!}

    <dl class="my-5 grid grid-cols-2 divide-gray-200 rounded-sm border border-gray-200 bg-white dark:divide-gray-800 dark:border-gray-800 dark:bg-gray-900 md:grid-cols-6">
        @foreach (['sales-today' => core()->formatBasePrice($summary['sales_today']), 'orders-today' => $summary['orders_today'], 'pending-orders' => $summary['pending_orders'], 'total-products' => $summary['total_products'], 'total-customers' => $summary['total_customers'], 'low-stock' => $summary['low_stock_count']] as $label => $value)
            <div class="min-w-0 border-b border-gray-200 p-4 dark:border-gray-800 md:border-b-0">
                <dt class="text-sm text-gray-600 dark:text-gray-300">{{ trans('shop_setup::merchant.'.$label) }}</dt>
                <dd class="mt-2 break-words text-2xl font-semibold tabular-nums text-gray-900 dark:text-white">{{ $value }}</dd>
            </div>
        @endforeach
    </dl>
    <p class="mb-5 text-sm text-gray-600 dark:text-gray-300">@lang('shop_setup::merchant.sales-hint')</p>

    @if (bouncer()->hasPermission('sales.orders'))
        <section class="mb-5 rounded-sm border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
            <div class="mb-4 flex items-center justify-between gap-3">
                <h2 class="text-base font-semibold text-gray-900 dark:text-white">@lang('shop_setup::merchant.recent-orders')</h2>
                <a href="{{ route('admin.sales.orders.index') }}" class="text-blue-700 underline dark:text-blue-300">@lang('shop_setup::merchant.view-orders')</a>
            </div>
            @include('shop_setup::admin.orders.table', ['orders' => $summary['recent_orders']])
        </section>
    @endif

    <div class="grid items-start gap-5 xl:grid-cols-2">
        @if (bouncer()->hasPermission('catalog.products'))
            <section class="min-w-0 rounded-sm border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                <h2 class="mb-2 text-base font-semibold text-gray-900 dark:text-white">@lang('shop_setup::merchant.low-stock')</h2>
                <p class="mb-4 text-sm text-gray-600 dark:text-gray-300">{{ trans('shop_setup::merchant.stock-hint', ['quantity' => $summary['threshold']]) }}</p>
                <ul class="divide-y divide-gray-200 dark:divide-gray-800">
                    @forelse ($summary['low_stock'] as $inventory)
                        <li class="flex items-center justify-between gap-4 py-3">
                            @if (bouncer()->hasPermission('catalog.products.edit'))
                                <a href="{{ route('admin.catalog.products.edit', $inventory->product_id) }}" class="min-w-0 break-words text-blue-700 underline dark:text-blue-300">{{ $inventory->product->name ?: $inventory->product->sku }}</a>
                            @else
                                <span>{{ $inventory->product->name ?: $inventory->product->sku }}</span>
                            @endif
                            <span class="shrink-0 font-semibold tabular-nums">{{ $inventory->qty }}</span>
                        </li>
                    @empty
                        <li class="py-3 text-gray-600 dark:text-gray-300">@lang('shop_setup::merchant.no-low-stock')</li>
                    @endforelse
                </ul>
            </section>
        @endif

        <section class="min-w-0 rounded-sm border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
            <h2 class="mb-4 text-base font-semibold text-gray-900 dark:text-white">@lang('shop_setup::merchant.sales-trend') · @lang('shop_setup::merchant.sales-period')</h2>
            @if ($summary['sales_trend']->every(fn ($amount) => $amount == 0))
                <p class="py-3 text-gray-600 dark:text-gray-300">@lang('shop_setup::merchant.no-sales')</p>
            @else
            <dl class="divide-y divide-gray-200 dark:divide-gray-800">
                @foreach ($summary['sales_trend'] as $date => $amount)
                    <div class="flex items-center justify-between gap-3 py-2">
                        <dt>{{ \Illuminate\Support\Carbon::parse($date)->translatedFormat('D j M') }}</dt>
                        <dd class="font-medium tabular-nums">{{ core()->formatBasePrice($amount) }}</dd>
                    </div>
                @endforeach
            </dl>
            @endif
        </section>

        <section class="min-w-0 rounded-sm border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
            <h2 class="mb-3 text-base font-semibold text-gray-900 dark:text-white">@lang('shop_setup::merchant.best-selling')</h2>
            <ul class="divide-y divide-gray-200 dark:divide-gray-800">
                @forelse ($summary['best_selling'] as $product)
                    <li class="flex justify-between gap-4 py-3"><span class="min-w-0 break-words">{{ $product['name'] }}</span><span class="shrink-0 tabular-nums">{{ $product['formatted_revenue'] }}</span></li>
                @empty
                    <li class="py-3 text-gray-600 dark:text-gray-300">@lang('shop_setup::merchant.no-sales')</li>
                @endforelse
            </ul>
        </section>
        <section class="min-w-0">
            <h2 class="mb-3 text-base font-semibold text-gray-900 dark:text-white">@lang('shop_setup::merchant.quick-actions')</h2>
            <div class="flex flex-wrap gap-3">
                @if (bouncer()->hasPermission('configuration'))
                    <a href="{{ route('admin.settings.merchant.index') }}" class="secondary-button">@lang('shop_setup::merchant.settings')</a>
                @endif
                @if (bouncer()->hasPermission('appearance'))
                    <a href="{{ route('admin.appearance.merchant.index') }}" class="secondary-button">@lang('shop_setup::merchant.store-design')</a>
                @endif
                <a href="{{ route('shop.home.index') }}" class="secondary-button" target="_blank" rel="noopener">@lang('shop_setup::merchant.view-store')</a>
            </div>
        </section>
    </div>
</x-admin::layouts>
