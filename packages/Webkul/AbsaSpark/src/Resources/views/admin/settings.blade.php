<x-admin::layouts>
    <x-slot:title>@lang('absa_spark::app.settings.title')</x-slot>

    <x-admin::form method="PUT" :action="route('admin.settings.absa_spark.update')" autocomplete="off">
        <div class="mb-5 flex flex-wrap items-center justify-between gap-4">
            <h1 class="text-xl font-bold text-gray-800 dark:text-white">@lang('absa_spark::app.settings.title')</h1>
            <button class="primary-button">@lang('absa_spark::app.settings.save')</button>
        </div>

        <div class="max-w-3xl rounded-sm border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
            <p class="mb-4 text-sm text-gray-700 dark:text-gray-300">@lang('absa_spark::app.settings.description')</p>

            @if ($baseCurrency !== config('absa-spark.currency'))
                <p role="alert" class="mb-4 rounded-sm border border-yellow-400 p-3 text-yellow-800 dark:text-yellow-300">{{ trans('absa_spark::app.settings.mur-only', ['currency' => $baseCurrency]) }}</p>
            @endif

            @if ($errors->any())
                <div role="alert" class="mb-4 text-red-700 dark:text-red-300">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <x-admin::form.control-group>
                <x-admin::form.control-group.label for="active">@lang('absa_spark::app.settings.enabled')</x-admin::form.control-group.label>
                <x-admin::form.control-group.control type="select" id="active" name="active" :value="old('active', $settings->enabled() ? '1' : '0')" :label="trans('absa_spark::app.settings.enabled')">
                    <option value="1">@lang('absa_spark::app.settings.yes')</option>
                    <option value="0">@lang('absa_spark::app.settings.no')</option>
                </x-admin::form.control-group.control>
            </x-admin::form.control-group>

            <x-admin::form.control-group>
                <x-admin::form.control-group.label for="title">@lang('absa_spark::app.settings.checkout-title')</x-admin::form.control-group.label>
                <x-admin::form.control-group.control type="text" id="title" name="title" :value="old('title', $settings->get('title') ?: config('payment_methods.absaspark.title'))" rules="required" :label="trans('absa_spark::app.settings.checkout-title')" />
                <x-admin::form.control-group.error control-name="title" />
            </x-admin::form.control-group>

            <x-admin::form.control-group>
                <x-admin::form.control-group.label for="sandbox">@lang('absa_spark::app.settings.mode')</x-admin::form.control-group.label>
                <x-admin::form.control-group.control type="select" id="sandbox" name="sandbox" :value="old('sandbox', $settings->sandbox() ? '1' : '0')" :label="trans('absa_spark::app.settings.mode')">
                    <option value="1">@lang('absa_spark::app.settings.sandbox')</option>
                    <option value="0">@lang('absa_spark::app.settings.live')</option>
                </x-admin::form.control-group.control>
            </x-admin::form.control-group>

            <x-admin::form.control-group>
                <x-admin::form.control-group.label for="store_id">@lang('absa_spark::app.settings.store-id')</x-admin::form.control-group.label>
                <x-admin::form.control-group.control type="text" id="store_id" name="store_id" :value="old('store_id', $settings->storeId())" :label="trans('absa_spark::app.settings.store-id')" />
                <x-admin::form.control-group.error control-name="store_id" />
            </x-admin::form.control-group>

            @foreach (['api_key' => 'api-key', 'secret' => 'secret'] as $field => $label)
                <x-admin::form.control-group>
                    <x-admin::form.control-group.label :for="$field">{{ trans('absa_spark::app.settings.'.$label) }}</x-admin::form.control-group.label>
                    <x-admin::form.control-group.control type="password" :id="$field" :name="$field" value="" autocomplete="new-password" :label="trans('absa_spark::app.settings.'.$label)" />
                    <p class="mt-1 text-sm text-gray-700 dark:text-gray-300">{{ trans($settings->hasSecret($field) ? 'absa_spark::app.settings.saved-secret' : 'absa_spark::app.settings.not-set') }}</p>
                    <x-admin::form.control-group.error :control-name="$field" />
                </x-admin::form.control-group>
            @endforeach

            <div class="mt-2 border-t border-gray-200 pt-4 dark:border-gray-800">
                <p class="text-sm font-medium text-gray-900 dark:text-white">@lang('absa_spark::app.settings.webhook-url')</p>
                <code class="mt-1 block break-all text-sm text-gray-800 dark:text-gray-200">{{ $webhookUrl }}</code>
                <p class="mt-1 text-sm text-gray-700 dark:text-gray-300">@lang('absa_spark::app.settings.webhook-hint')</p>
            </div>
        </div>
    </x-admin::form>
</x-admin::layouts>
