<x-admin::layouts>
    <x-slot:title>
        @lang('shop_setup::app.tax.title')
    </x-slot>

    <div class="grid gap-1.5">
        <p class="text-xl font-bold text-gray-800 dark:text-white">
            @lang('shop_setup::app.tax.title')
        </p>

        <p class="text-xs font-medium text-gray-500 dark:text-gray-300">
            @lang('shop_setup::app.tax.info')
        </p>
    </div>

    @if ($errors->any())
        <ul class="mt-4 list-disc rounded bg-red-50 p-4 pl-8 text-sm text-red-700">
            @foreach ($errors->all() as $message)
                <li>{{ $message }}</li>
            @endforeach
        </ul>
    @endif

    <v-shop-tax
        :current='@json($current)'
        :presets='@json($presets)'
        :countries='@json($countries)'
        action="{{ route('admin.settings.shop_tax.update') }}"
        rates-url="{{ route('admin.settings.taxes.rates.index') }}"
        csrf="{{ csrf_token() }}"
    >
        <x-admin::shimmer.image class="mt-8 h-[300px] w-full rounded" />
    </v-shop-tax>

    @pushOnce('scripts')
        <script
            type="text/x-template"
            id="v-shop-tax-template"
        >
            <form
                method="POST"
                :action="action"
                class="mt-6 grid max-w-3xl gap-6"
            >
                <input type="hidden" name="_token" :value="csrf">
                <input type="hidden" name="_method" value="PUT">

                <p
                    class="rounded bg-yellow-50 p-4 text-sm font-medium text-yellow-800"
                    v-if="! current.confirmed"
                >
                    @lang('shop_setup::app.tax.not-confirmed')
                </p>

                <fieldset class="grid gap-3">
                    <legend class="sr-only">@lang('shop_setup::app.tax.title')</legend>

                    <label
                        class="box-shadow flex cursor-pointer items-start gap-3 rounded bg-white p-4 dark:bg-gray-900"
                        :class="{ 'ring-2 ring-blue-600': mode === option.value }"
                        v-for="option in modes"
                        :key="option.value"
                    >
                        <input
                            class="mt-1"
                            type="radio"
                            name="mode"
                            :value="option.value"
                            v-model="mode"
                        >

                        <span class="grid gap-1">
                            <span class="text-base font-semibold text-gray-800 dark:text-white">@{{ option.title }}</span>

                            <span class="text-xs text-gray-600 dark:text-gray-300">@{{ option.info }}</span>
                        </span>
                    </label>
                </fieldset>

                <div
                    class="box-shadow grid gap-4 rounded bg-white p-4 dark:bg-gray-900"
                    v-if="mode !== 'none'"
                >
                    <label class="grid gap-1.5">
                        <span class="text-sm font-medium text-gray-800 dark:text-white">@lang('shop_setup::app.tax.country')</span>

                        <select
                            class="rounded border px-3 py-2 text-sm dark:bg-gray-900 dark:text-white"
                            name="country"
                            v-model="country"
                            required
                        >
                            <option value="" disabled>@lang('shop_setup::app.tax.choose-country')</option>

                            <option
                                v-for="item in countries"
                                :key="item.code"
                                :value="item.code"
                            >@{{ item.name }}</option>
                        </select>
                    </label>

                    <div
                        class="grid gap-4 sm:grid-cols-2"
                        v-if="mode === 'added' || mode === 'included'"
                    >
                        <label class="grid gap-1.5">
                            <span class="text-sm font-medium text-gray-800 dark:text-white">@lang('shop_setup::app.tax.name')</span>

                            <input
                                class="rounded border px-3 py-2 text-sm dark:bg-gray-900 dark:text-white"
                                type="text"
                                name="name"
                                maxlength="40"
                                v-model="name"
                                required
                            >

                            <span class="text-xs text-gray-500">@lang('shop_setup::app.tax.name-hint')</span>
                        </label>

                        <label class="grid gap-1.5">
                            <span class="text-sm font-medium text-gray-800 dark:text-white">@lang('shop_setup::app.tax.rate')</span>

                            <input
                                class="rounded border px-3 py-2 text-sm dark:bg-gray-900 dark:text-white"
                                type="number"
                                name="rate"
                                min="0.01"
                                max="99.99"
                                step="0.01"
                                v-model="rate"
                                required
                            >

                            <span
                                class="text-xs text-gray-500"
                                v-if="preset"
                            >@lang('shop_setup::app.tax.preset-hint')</span>
                        </label>
                    </div>

                    <div
                        class="grid gap-3"
                        v-if="mode === 'by_country'"
                    >
                        <p class="text-xs text-gray-600 dark:text-gray-300">
                            @lang('shop_setup::app.tax.by-country-info')
                        </p>

                        <a
                            class="text-sm font-medium text-blue-600 hover:underline"
                            :href="ratesUrl"
                        >@lang('shop_setup::app.tax.by-country-link')</a>

                        <label class="grid gap-1.5 sm:max-w-xs">
                            <span class="text-sm font-medium text-gray-800 dark:text-white">@lang('shop_setup::app.tax.other-rate')</span>

                            <input
                                class="rounded border px-3 py-2 text-sm dark:bg-gray-900 dark:text-white"
                                type="number"
                                name="other_rate"
                                min="0"
                                max="99.99"
                                step="0.01"
                                v-model="otherRate"
                            >

                            <span class="text-xs text-gray-500">@lang('shop_setup::app.tax.other-rate-hint')</span>
                        </label>
                    </div>
                </div>

                <ul class="grid list-disc gap-2 pl-5 text-xs text-gray-600 dark:text-gray-300">
                    <li v-if="mode !== 'none'">@lang('shop_setup::app.tax.exempt-hint')</li>

                    <li v-if="mode === 'included'">@lang('shop_setup::app.tax.included-discount-note')</li>

                    <li>@lang('shop_setup::app.tax.disclaimer')</li>
                </ul>

                <div>
                    <button
                        type="submit"
                        class="primary-button"
                    >@lang('shop_setup::app.tax.save')</button>
                </div>
            </form>
        </script>

        <script type="module">
            app.component('v-shop-tax', {
                template: '#v-shop-tax-template',

                props: ['current', 'presets', 'countries', 'action', 'ratesUrl', 'csrf'],

                data() {
                    return {
                        mode: this.current.mode,
                        country: this.current.country || '',
                        name: this.current.name || '',
                        rate: this.current.rate ?? '',
                        otherRate: this.current.other_rate ?? '',
                        modes: [
                            { value: 'none', title: @js(trans('shop_setup::app.tax.modes.none.title')), info: @js(trans('shop_setup::app.tax.modes.none.info')) },
                            { value: 'added', title: @js(trans('shop_setup::app.tax.modes.added.title')), info: @js(trans('shop_setup::app.tax.modes.added.info')) },
                            { value: 'included', title: @js(trans('shop_setup::app.tax.modes.included.title')), info: @js(trans('shop_setup::app.tax.modes.included.info')) },
                            { value: 'by_country', title: @js(trans('shop_setup::app.tax.modes.by-country.title')), info: @js(trans('shop_setup::app.tax.modes.by-country.info')) },
                        ],
                    };
                },

                computed: {
                    preset() {
                        return this.presets[this.country] || null;
                    },
                },

                watch: {
                    country(code, previous) {
                        const next = this.presets[code];

                        if (! next) {
                            return;
                        }

                        const before = this.presets[previous];

                        if (this.name === '' || (before && this.name === before.name)) {
                            this.name = next.name;
                        }

                        if (this.rate === '' || (before && Number(this.rate) === before.rate)) {
                            this.rate = next.rate;
                        }
                    },
                },
            });
        </script>
    @endPushOnce
</x-admin::layouts>
