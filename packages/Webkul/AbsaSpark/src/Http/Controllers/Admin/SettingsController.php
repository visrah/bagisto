<?php

namespace Webkul\AbsaSpark\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Webkul\AbsaSpark\Services\AbsaSettings;
use Webkul\Admin\Http\Controllers\Controller;

class SettingsController extends Controller
{
    /**
     * Create the controller.
     */
    public function __construct(protected AbsaSettings $settings) {}

    /**
     * Show the credentials form without revealing any saved secret.
     */
    public function index(): View
    {
        return view('absa_spark::admin.settings', [
            'settings' => $this->settings,
            'webhookUrl' => route('absa_spark.webhook'),
            'baseCurrency' => core()->getBaseCurrencyCode(),
        ]);
    }

    /**
     * Save the credentials, keeping a saved secret when its field is left blank.
     */
    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'active' => ['required', 'in:0,1'],
            'title' => ['required', 'string', 'max:100'],
            'sandbox' => ['required', 'in:0,1'],
            'store_id' => ['nullable', 'string', 'max:100'],
            'api_key' => ['nullable', 'string', 'max:255'],
            'secret' => ['nullable', 'string', 'max:255'],
        ]);

        $complete = filled($data['store_id'] ?? $this->settings->storeId())
            && (filled($data['api_key'] ?? null) || $this->settings->hasSecret('api_key'))
            && (filled($data['secret'] ?? null) || $this->settings->hasSecret('secret'));

        if ($data['active'] === '1' && ! $complete) {
            throw ValidationException::withMessages(['active' => trans('absa_spark::app.settings.credentials-required')]);
        }

        $this->settings->save($data);

        session()->flash('success', trans('absa_spark::app.settings.updated'));

        return redirect()->route('admin.settings.absa_spark.index');
    }

    /**
     * Switch Absa Spark on or off without touching the saved credentials.
     */
    public function toggle(Request $request): RedirectResponse
    {
        $data = $request->validate(['active' => ['required', 'in:0,1']]);

        if ($data['active'] === '1' && ! $this->settings->isConfigured()) {
            session()->flash('error', trans('absa_spark::app.settings.credentials-required'));

            return redirect()->back();
        }

        $this->settings->save(['active' => $data['active']]);

        session()->flash('success', trans('absa_spark::app.settings.updated'));

        return redirect()->back();
    }
}
