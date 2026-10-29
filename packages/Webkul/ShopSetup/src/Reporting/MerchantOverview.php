<?php

namespace Webkul\ShopSetup\Reporting;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Webkul\Admin\Helpers\Reporting\Product as ProductReporting;
use Webkul\Customer\Repositories\CustomerRepository;
use Webkul\Product\Repositories\ProductInventoryRepository;
use Webkul\Product\Repositories\ProductRepository;
use Webkul\Sales\Models\Invoice;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Repositories\InvoiceRepository;
use Webkul\Sales\Repositories\OrderRepository;
use Webkul\Sales\Repositories\RefundRepository;

class MerchantOverview
{
    /**
     * Create the merchant overview reader.
     */
    public function __construct(
        protected OrderRepository $orders,
        protected InvoiceRepository $invoices,
        protected RefundRepository $refunds,
        protected ProductRepository $products,
        protected ProductInventoryRepository $inventories,
        protected CustomerRepository $customers,
        protected ProductReporting $productReporting,
    ) {}

    /**
     * Read bounded lists and current totals for the default store without caching commerce data.
     */
    public function dashboard(): array
    {
        $channel = core()->getDefaultChannel();
        $source = $channel->inventory_sources->where('status', 1)->sortBy('priority')->first();
        $threshold = (int) config('shop_setup.low_stock_threshold', 5);

        $stock = $this->inventories->resetModel()
            ->where('inventory_source_id', $source?->id)
            ->where('qty', '<=', $threshold)
            ->whereHas('product', function ($query) use ($channel) {
                $query->whereHas('channels', fn ($query) => $query->where('channels.id', $channel->id))
                    ->whereHas('attribute_values', fn ($query) => $query->where('boolean_value', 1)
                        ->whereHas('attribute', fn ($query) => $query->where('code', 'manage_stock')));
            });

        $stockCount = (clone $stock)->count();
        $stock = $stock->with(['product.attribute_values', 'product.attribute_family', 'product.images'])->orderBy('qty')->orderBy('id')->limit(5)->get();

        $sales = $this->dailySales();

        return [
            'sales_today' => $sales->get(now()->format('Y-m-d'), 0.0),
            'orders_today' => $this->orders->resetModel()->where('channel_id', $channel->id)->whereBetween('created_at', [now()->startOfDay(), now()->endOfDay()])->count(),
            'pending_orders' => $this->orders->resetModel()->where('channel_id', $channel->id)->whereIn('status', [Order::STATUS_PENDING, Order::STATUS_PENDING_PAYMENT, Order::STATUS_PROCESSING])->count(),
            'total_products' => $this->products->resetModel()->whereNull('parent_id')->whereHas('channels', fn ($query) => $query->where('channels.id', $channel->id))->count(),
            'total_customers' => $this->customers->resetModel()->count(),
            'low_stock_count' => $stockCount,
            'low_stock' => $stock,
            'threshold' => $threshold,
            'recent_orders' => $this->orders->resetModel()->with(['invoices', 'items'])->where('channel_id', $channel->id)->latest('id')->limit(5)->get(),
            'sales_trend' => $sales,
            'best_selling' => $this->productReporting->getTopSellingProductsByRevenue(5),
        ];
    }

    /**
     * Paginate merchant orders with their payment and fulfilment data loaded together.
     */
    public function orders(): LengthAwarePaginator
    {
        $filters = request()->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:pending,pending_payment,processing,completed,canceled,closed,fraud'],
            'payment' => ['nullable', 'in:awaiting,paid'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        return $this->orders->resetModel()->with(['invoices', 'items'])
            ->where('channel_id', core()->getDefaultChannel()->id)
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where(function ($query) use ($search) {
                $query->where('increment_id', db_grammar()->caseInsensitiveLike(), '%'.$search.'%')
                    ->orWhere('customer_email', db_grammar()->caseInsensitiveLike(), '%'.$search.'%')
                    ->orWhere('customer_first_name', db_grammar()->caseInsensitiveLike(), '%'.$search.'%')
                    ->orWhere('customer_last_name', db_grammar()->caseInsensitiveLike(), '%'.$search.'%');
            }))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['payment'] ?? null, fn ($query, $payment) => $payment === 'paid'
                ? $query->whereHas('invoices', fn ($query) => $query->where('state', Invoice::STATUS_PAID))
                : $query->whereDoesntHave('invoices', fn ($query) => $query->where('state', Invoice::STATUS_PAID))->whereNotIn('status', [Order::STATUS_CANCELED, Order::STATUS_CLOSED]))
            ->latest('id')->paginate(20)->withQueryString();
    }

    /**
     * Group paid invoices and recorded refunds by their own date, including days with no sales.
     */
    protected function dailySales(): Collection
    {
        $date = db_grammar()->dateFormat('created_at', '%Y-%m-%d');
        $start = now()->subDays(6)->startOfDay();
        $end = now()->endOfDay();
        $channelId = core()->getDefaultChannel()->id;

        $invoices = $this->invoices->resetModel()->where('state', Invoice::STATUS_PAID)
            ->whereHas('order', fn ($query) => $query->where('channel_id', $channelId))
            ->whereBetween('created_at', [$start, $end])->selectRaw($date.' AS day, SUM(base_grand_total) AS total')->groupByRaw($date)->pluck('total', 'day');

        $refunds = $this->refunds->resetModel()
            ->whereHas('order', fn ($query) => $query->where('channel_id', $channelId))
            ->whereBetween('created_at', [$start, $end])->selectRaw($date.' AS day, SUM(base_grand_total) AS total')->groupByRaw($date)->pluck('total', 'day');

        return collect(range(6, 0))->mapWithKeys(function (int $days) use ($invoices, $refunds) {
            $day = now()->subDays($days)->format('Y-m-d');

            return [$day => (float) $invoices->get($day, 0) - (float) $refunds->get($day, 0)];
        });
    }
}
