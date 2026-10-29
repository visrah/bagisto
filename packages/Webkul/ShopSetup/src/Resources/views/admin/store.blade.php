<x-admin::layouts>
    <x-slot:title>@lang('shop_setup::merchant.store-information')</x-slot>

    <x-admin::form method="PUT" enctype="multipart/form-data" :action="route('admin.settings.merchant.update')">
        <div class="mb-5 flex flex-wrap items-center justify-between gap-4">
            <h1 class="text-xl font-bold text-gray-800 dark:text-white">@lang('shop_setup::merchant.store-information')</h1>
            <button class="primary-button">@lang('shop_setup::merchant.save')</button>
        </div>

        <div class="max-w-3xl rounded-sm border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
            @if ($errors->any())
                <p role="alert" class="mb-4 text-red-700 dark:text-red-300">@lang('shop_setup::merchant.error-summary')</p>
            @endif

            @foreach (['name', 'description', 'email', 'contact', 'address', 'city', 'state', 'country', 'zipcode'] as $field)
                @php
                    $label = trans('shop_setup::merchant.'.($field === 'contact' ? 'phone' : $field));
                    $value = match ($field) {
                        'name', 'description' => $channel->translate($channel->default_locale->code)?->{$field},
                        'email' => core()->getConfigData('emails.configure.email_settings.contact_email'),
                        default => $values->get('sales.shipping.origin.'.$field),
                    };
                @endphp

                <x-admin::form.control-group>
                    <x-admin::form.control-group.label :for="$field">{{ $label }}</x-admin::form.control-group.label>
                    @if ($field === 'country')
                        <x-admin::form.control-group.control type="select" :id="$field" :name="$field" :value="old($field, $value)" :label="$label" rules="required">
                            <option value="">{{ $label }}</option>
                            @foreach (core()->countries() as $country)
                                <option value="{{ $country->code }}">{{ $country->name }}</option>
                            @endforeach
                        </x-admin::form.control-group.control>
                    @else
                        <x-admin::form.control-group.control :type="$field === 'description' ? 'textarea' : ($field === 'email' ? 'email' : 'text')" :id="$field" :name="$field" :value="old($field, $value)" :label="$label" :rules="in_array($field, ['name', 'email', 'address', 'city']) ? 'required' : ''" />
                    @endif
                    <x-admin::form.control-group.error :control-name="$field" />
                </x-admin::form.control-group>
            @endforeach

            <x-admin::form.control-group>
                <x-admin::form.control-group.label for="store-logo">@lang('shop_setup::merchant.logo')</x-admin::form.control-group.label>
                @if ($channel->logo_url)
                    <img src="{{ $channel->logo_url }}" alt="{{ $channel->name }}" class="mb-3 h-12 max-w-full object-contain">
                @endif
                <input id="store-logo" type="file" name="logo[]" accept="image/jpeg,image/png,image/webp" class="block w-full text-sm">
                <x-admin::form.control-group.error control-name="logo.0" />
            </x-admin::form.control-group>
        </div>
    </x-admin::form>
</x-admin::layouts>
