@inject('orderLabels', \Webkul\ShopSetup\Sales\OrderLabels::class)

<dl class="flex flex-wrap gap-x-6 gap-y-2 rounded-sm border border-gray-200 p-3 text-sm dark:border-gray-800">
    <div>
        <dt class="text-gray-600 dark:text-gray-300">@lang('shop_setup::merchant.payment-status')</dt>
        <dd class="mt-1 font-semibold">{{ trans('shop_setup::merchant.'.$orderLabels->payment($order)) }}</dd>
    </div>
    <div>
        <dt class="text-gray-600 dark:text-gray-300">@lang('shop_setup::merchant.fulfilment-status')</dt>
        <dd class="mt-1 font-semibold">{{ trans('shop_setup::merchant.'.$orderLabels->fulfilment($order)) }}</dd>
    </div>
</dl>

@if ($nextStep = $orderLabels->nextStep($order))
    <p class="mt-2 text-sm text-gray-700 dark:text-gray-300">{{ trans('shop_setup::merchant.'.$nextStep) }}</p>
@endif
