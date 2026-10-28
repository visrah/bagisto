@inject('checklist', 'Webkul\ShopSetup\Checklist\SetupChecklist')

@php
    $items = $checklist->items();
    $remaining = collect($items)->where('done', false)->count();
@endphp

@if ($remaining > 0)
    <div class="box-shadow mb-4 grid gap-3 rounded bg-white p-4 dark:bg-gray-900">
        <div class="flex flex-wrap items-baseline justify-between gap-2">
            <p class="text-base font-semibold text-gray-800 dark:text-white">
                @lang('shop_setup::app.checklist.title')
            </p>

            <p class="text-xs font-medium text-gray-500 dark:text-gray-300">
                @lang('shop_setup::app.checklist.progress', ['done' => count($items) - $remaining, 'total' => count($items)])
            </p>
        </div>

        <ul class="grid gap-2">
            @foreach ($items as $item)
                <li class="flex items-start gap-3">
                    <span
                        class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full text-xs font-bold {{ $item['done'] ? 'bg-green-600 text-white' : 'border border-gray-400 text-transparent' }}"
                        aria-hidden="true"
                    >&#10003;</span>

                    <a
                        class="grid gap-0.5 {{ $item['done'] ? 'opacity-60' : '' }}"
                        href="{{ $item['url'] }}"
                    >
                        <span class="text-sm font-medium text-gray-800 hover:underline dark:text-white">{{ $item['title'] }}</span>

                        <span class="text-xs text-gray-500 dark:text-gray-300">{{ $item['hint'] }}</span>

                        @if ($item['done'])
                            <span class="sr-only">@lang('shop_setup::app.checklist.done')</span>
                        @endif
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
@endif
