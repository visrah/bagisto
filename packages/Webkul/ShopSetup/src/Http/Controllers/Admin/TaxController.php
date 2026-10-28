<?php

namespace Webkul\ShopSetup\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\ShopSetup\Http\Requests\TaxChoiceRequest;
use Webkul\ShopSetup\Tax\CountryRates;
use Webkul\ShopSetup\Tax\TaxSetup;

class TaxController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        protected TaxSetup $taxSetup,
        protected CountryRates $countryRates,
    ) {}

    /**
     * Show the tax setup with the choice the shop runs on now.
     */
    public function index(): View
    {
        return view('shop_setup::admin.tax.index', [
            'current' => $this->taxSetup->current(),
            'presets' => $this->countryRates->all(),
            'countries' => core()->countries()->map(fn ($country) => ['code' => $country->code, 'name' => $country->name])->values(),
        ]);
    }

    /**
     * Apply the owner's tax choice.
     */
    public function update(TaxChoiceRequest $request): RedirectResponse
    {
        $this->taxSetup->apply(
            $request->input('mode'),
            $request->input('country'),
            $request->input('name'),
            $request->filled('rate') ? (float) $request->input('rate') : null,
            $request->filled('other_rate') ? (float) $request->input('other_rate') : null,
        );

        session()->flash('success', trans('shop_setup::app.tax.saved'));

        return redirect()->route('admin.settings.shop_tax.index');
    }
}
