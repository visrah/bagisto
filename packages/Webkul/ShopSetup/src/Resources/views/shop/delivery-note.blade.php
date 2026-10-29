@if ($product->getTypeInstance()->isStockable())
    <p class="mt-3 text-sm text-zinc-700">@lang('shop_setup::merchant.delivery-at-checkout')</p>
@endif
