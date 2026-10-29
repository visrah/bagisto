<x-admin::layouts>
    <x-slot:title>{{ trans('shop_setup::merchant.'.$title) }}</x-slot>

    <h1 class="mb-5 text-xl font-bold text-gray-800 dark:text-white">{{ trans('shop_setup::merchant.'.$title) }}</h1>

    <div class="max-w-3xl divide-y rounded-sm border border-gray-200 bg-white dark:divide-gray-800 dark:border-gray-800 dark:bg-gray-900">
        @foreach ($links as [$label, $destination, $parameters, $permission])
            @if (! $permission || bouncer()->hasPermission($permission))
                <a href="{{ route($destination, $parameters) }}" class="flex min-h-16 items-center justify-between gap-4 px-4 py-4 hover:bg-gray-50 focus-visible:outline-2 focus-visible:outline-offset-2 dark:hover:bg-gray-800">
                    <div>
                        <h2 class="font-semibold text-gray-800 dark:text-white">{{ trans('shop_setup::merchant.'.$label) }}</h2>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">{{ trans('shop_setup::merchant.'.$label.'-hint') }}</p>
                    </div>
                    <span class="icon-arrow-right text-xl rtl:rotate-180" aria-hidden="true"></span>
                </a>
            @endif
        @endforeach
    </div>
</x-admin::layouts>
