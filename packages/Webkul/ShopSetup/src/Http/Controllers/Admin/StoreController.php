<?php

namespace Webkul\ShopSetup\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\View\View;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Core\Repositories\ChannelRepository;
use Webkul\Core\Rules\PhoneNumber;
use Webkul\ShopSetup\Support\ConfigValues;

class StoreController extends Controller
{
    /**
     * Create the store settings controller.
     */
    public function __construct(protected ChannelRepository $channels, protected ConfigValues $values) {}

    /**
     * Show branding and contact information for the default store.
     */
    public function edit(): View
    {
        return view('shop_setup::admin.store', ['channel' => core()->getDefaultChannel(), 'values' => $this->values]);
    }

    /**
     * Save only merchant information, preserving channel wiring and existing media.
     */
    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:190'],
            'description' => ['nullable', 'string', 'max:5000'],
            'email' => ['required', 'email', 'max:190'],
            'contact' => ['nullable', new PhoneNumber, 'max:50'],
            'address' => ['required', 'string', 'max:190'],
            'city' => ['required', 'string', 'max:190'],
            'state' => ['nullable', 'string', 'max:190'],
            'country' => ['required', 'exists:countries,code'],
            'zipcode' => ['nullable', 'string', 'max:20'],
            'logo' => ['sometimes', 'array', 'max:1'],
            'logo.*' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $channel = core()->getDefaultChannel();

        DB::transaction(function () use ($data, $channel) {
            Event::dispatch('core.channel.update.before', $channel->id);

            $updated = $this->channels->update([
                $channel->default_locale->code => [
                    'name' => $data['name'],
                    'description' => clean_content($data['description'] ?? ''),
                ],
                'locales' => $channel->locales->pluck('id')->all(),
                'currencies' => $channel->currencies->pluck('id')->all(),
                'inventory_sources' => $channel->inventory_sources->pluck('id')->all(),
                'logo' => $data['logo'] ?? [$channel->logo],
                'favicon' => [$channel->favicon],
            ], $channel->id);

            foreach (['contact', 'address', 'city', 'state', 'country', 'zipcode'] as $field) {
                $this->values->set('sales.shipping.origin.'.$field, (string) ($data[$field] ?? ''));
            }

            $this->values->set('sales.shipping.origin.store_name', $data['name']);
            $this->values->set('emails.configure.email_settings.contact_email', $data['email']);

            Event::dispatch('core.channel.update.after', $updated);
            Event::dispatch('core.configuration.save.after');
        });

        session()->flash('success', trans('shop_setup::merchant.saved'));

        return redirect()->route('admin.settings.merchant.branding');
    }
}
