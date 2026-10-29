<x-admin::layouts>
    <x-slot:title>{{ trans('shop_setup::merchant.payments') }}</x-slot>

    <div class="mb-5 flex flex-wrap items-center justify-between gap-4">
        <h1 class="text-xl font-bold text-gray-800 dark:text-white">{{ trans('shop_setup::merchant.payments') }}</h1>
        <a href="{{ route('admin.configuration.index', ['slug' => 'sales', 'slug2' => 'payment_methods']) }}" class="primary-button">{{ trans('shop_setup::merchant.manage-payments') }}</a>
    </div>

    <p class="mb-4 max-w-3xl text-sm text-gray-600 dark:text-gray-300">{{ trans('shop_setup::merchant.payment-help') }}</p>

    @if (Route::has('admin.settings.absa_spark.index') && bouncer()->hasPermission('settings.absa_spark'))
        <a href="{{ route('admin.settings.absa_spark.index') }}" class="mb-4 inline-block font-medium text-blue-700 underline dark:text-blue-300">{{ trans('absa_spark::app.settings.title') }}</a>
    @endif

    <div class="max-w-3xl divide-y rounded-sm border border-gray-200 bg-white dark:divide-gray-800 dark:border-gray-800 dark:bg-gray-900">
        @foreach ($methods as $method)
            <div class="flex min-h-16 items-center justify-between gap-4 px-4 py-4">
                <div>
                    <h2 class="font-semibold text-gray-800 dark:text-white">{{ $method['title'] }}</h2>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">{{ trans($method['online'] ? 'shop_setup::merchant.payment-online' : 'shop_setup::merchant.payment-offline') }}</p>
                </div>
                <div class="flex items-center gap-3">
                    <span class="rounded-sm border px-2 py-1 text-sm font-medium {{ $method['active'] ? 'border-green-300 text-green-800 dark:text-green-300' : 'border-gray-300 text-gray-700 dark:text-gray-300' }}">
                        {{ trans($method['active'] ? 'shop_setup::merchant.enabled' : 'shop_setup::merchant.disabled') }}
                    </span>

                    @if ($method['code'] === 'absaspark' && Route::has('admin.settings.absa_spark.toggle') && bouncer()->hasPermission('settings.absa_spark'))
                        <form method="POST" action="{{ route('admin.settings.absa_spark.toggle') }}">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="active" value="{{ $method['active'] ? 0 : 1 }}">
                            <button type="submit" role="switch" aria-checked="{{ $method['active'] ? 'true' : 'false' }}" aria-label="{{ trans('absa_spark::app.settings.enabled') }}" class="relative inline-flex h-6 w-11 items-center rounded-full border border-gray-400 focus-visible:outline-2 focus-visible:outline-offset-2 {{ $method['active'] ? 'bg-green-600' : 'bg-gray-300 dark:bg-gray-700' }}">
                                <span class="inline-block h-4 w-4 rounded-full bg-white transition-transform {{ $method['active'] ? 'translate-x-6 rtl:-translate-x-6' : 'translate-x-1 rtl:-translate-x-1' }}"></span>
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</x-admin::layouts>
