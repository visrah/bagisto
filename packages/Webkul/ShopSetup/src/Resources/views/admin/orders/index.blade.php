<x-admin::layouts>
    <x-slot:title>@lang('shop_setup::merchant.orders')</x-slot>

    <h1 class="mb-5 text-xl font-bold text-gray-800 dark:text-white">@lang('shop_setup::merchant.orders')</h1>

    <form method="GET" class="mb-4 flex flex-wrap items-end gap-3">
        <div class="min-w-0 flex-1">
            <label for="order-search" class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">@lang('admin::app.sales.orders.index.datagrid.order-id') / @lang('admin::app.sales.orders.index.datagrid.customer')</label>
            <input id="order-search" name="search" type="search" value="{{ request('search') }}" class="min-h-11 w-full rounded-md border border-gray-300 px-3 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
        </div>
        <div>
            <label for="order-status" class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">@lang('admin::app.sales.orders.index.datagrid.status')</label>
            <select id="order-status" name="status" class="min-h-11 rounded-md border border-gray-300 px-3 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                <option value="">@lang('shop_setup::merchant.all-orders')</option>
                @foreach (['pending', 'pending_payment', 'processing', 'completed', 'canceled', 'closed', 'fraud'] as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ trans('shop_setup::merchant.'.($status === 'canceled' ? 'cancelled' : $status)) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="order-payment" class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">@lang('shop_setup::merchant.payment-status')</label>
            <select id="order-payment" name="payment" class="min-h-11 rounded-md border border-gray-300 px-3 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                <option value="">@lang('shop_setup::merchant.all-orders')</option>
                @foreach (['awaiting' => 'awaiting-payment', 'paid' => 'paid'] as $value => $label)
                    <option value="{{ $value }}" @selected(request('payment') === $value)>{{ trans('shop_setup::merchant.'.$label) }}</option>
                @endforeach
            </select>
        </div>
        <button class="secondary-button min-h-11">@lang('admin::app.components.datagrid.toolbar.search.title')</button>
    </form>

    <div class="rounded-sm border border-gray-200 bg-white p-2 dark:border-gray-800 dark:bg-gray-900">
        @include('shop_setup::admin.orders.table')
    </div>
    <div class="mt-4">{{ $orders->links() }}</div>
</x-admin::layouts>
