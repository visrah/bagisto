<x-admin::layouts>
    <x-slot:title>{{ trans('shop_setup::merchant.inventory') }}</x-slot>

    <div class="mb-5 flex flex-wrap items-center justify-between gap-4">
        <h1 class="text-xl font-bold text-gray-800 dark:text-white">{{ trans('shop_setup::merchant.inventory') }}</h1>
        <a href="{{ route('admin.configuration.index', ['slug' => 'catalog', 'slug2' => 'inventory']) }}" class="transparent-button dark:text-white">{{ trans('shop_setup::merchant.stock-settings') }}</a>
    </div>

    <p class="mb-4 text-sm text-gray-600 dark:text-gray-300">{{ trans('shop_setup::merchant.stock-location') }}: {{ $source?->name ?? '—' }}. {{ trans('shop_setup::merchant.stock-hint', ['quantity' => $threshold]) }}</p>

    @if ($stock->isEmpty())
        <p class="rounded-sm border border-gray-200 p-4 dark:border-gray-800">{{ trans('shop_setup::merchant.no-stock') }}</p>
    @else
        <div class="overflow-x-auto rounded-sm border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
            <table class="w-full text-left text-sm text-gray-900 dark:text-white">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-gray-800">
                        <th scope="col" class="px-4 py-3">{{ trans('shop_setup::merchant.product-name') }}</th>
                        <th scope="col" class="px-4 py-3">{{ trans('shop_setup::merchant.sku') }}</th>
                        <th scope="col" class="px-4 py-3">{{ trans('shop_setup::merchant.quantity') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y dark:divide-gray-800">
                    @foreach ($stock as $row)
                        <tr>
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.catalog.products.edit', $row->product_id) }}" class="font-medium text-blue-700 underline dark:text-blue-300">{{ $row->product->name ?: $row->product->sku }}</a>
                            </td>
                            <td class="px-4 py-3">{{ $row->product->sku }}</td>
                            <td class="px-4 py-3 {{ $row->qty <= $threshold ? 'font-semibold text-red-700 dark:text-red-300' : '' }}">{{ $row->qty }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $stock->links() }}</div>
    @endif
</x-admin::layouts>
