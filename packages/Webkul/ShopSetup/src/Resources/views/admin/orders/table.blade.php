@inject('orderLabels', \Webkul\ShopSetup\Sales\OrderLabels::class)

<div class="overflow-x-auto">
    <table class="w-full text-start text-sm text-gray-900 dark:text-white">
        <thead class="border-b border-gray-200 text-gray-600 dark:border-gray-800 dark:text-gray-300">
            <tr>
                @foreach (['admin::app.sales.orders.index.datagrid.order-id', 'admin::app.sales.orders.index.datagrid.customer', 'admin::app.sales.orders.index.datagrid.date', 'admin::app.sales.orders.index.datagrid.grand-total', 'shop_setup::merchant.payment-status', 'shop_setup::merchant.fulfilment-status'] as $label)
                    <th scope="col" class="whitespace-nowrap px-3 py-3 text-start font-medium">{{ trans($label) }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
            @forelse ($orders as $order)
                <tr>
                    <th scope="row" class="px-3 py-4 text-start font-semibold">
                        @if (bouncer()->hasPermission('sales.orders.view'))
                            <a class="text-blue-700 underline dark:text-blue-300" href="{{ route('admin.sales.orders.view', $order->id) }}">#{{ $order->increment_id }}</a>
                        @else
                            #{{ $order->increment_id }}
                        @endif
                        <p class="mt-1 text-xs font-normal text-gray-600 dark:text-gray-300">{{ trans('shop_setup::merchant.'.($order->status === 'canceled' ? 'cancelled' : $order->status)) }}</p>
                    </th>
                    <td class="max-w-56 break-words px-3 py-4">{{ $order->customer_full_name }}</td>
                    <td class="whitespace-nowrap px-3 py-4">{{ $order->created_at->format('d M Y') }}</td>
                    <td class="whitespace-nowrap px-3 py-4 tabular-nums">{{ core()->formatBasePrice($order->base_grand_total) }}</td>
                    <td class="px-3 py-4">{{ trans('shop_setup::merchant.'.$orderLabels->payment($order)) }}</td>
                    <td class="px-3 py-4">{{ trans('shop_setup::merchant.'.$orderLabels->fulfilment($order)) }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-3 py-6 text-gray-600 dark:text-gray-300">@lang('shop_setup::merchant.no-orders')</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
