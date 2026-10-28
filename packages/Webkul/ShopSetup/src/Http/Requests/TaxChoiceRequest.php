<?php

namespace Webkul\ShopSetup\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Webkul\ShopSetup\Tax\TaxSetup;

class TaxChoiceRequest extends FormRequest
{
    /**
     * The admin middleware and the role's permissions decide who may save the tax setup.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * The validation rules: a taxed mode needs the country, a name and a rate, and the optional rate for other countries only applies to rates by country.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $taxed = Rule::requiredIf(fn () => $this->input('mode') !== TaxSetup::MODE_NONE);

        $rated = Rule::requiredIf(fn () => in_array($this->input('mode'), [TaxSetup::MODE_ADDED, TaxSetup::MODE_INCLUDED], true));

        return [
            'mode' => ['required', Rule::in(TaxSetup::modes())],
            'country' => [$taxed, 'nullable', 'string', 'size:2', Rule::exists('countries', 'code')],
            'name' => [$rated, 'nullable', 'string', 'max:40'],
            'rate' => [$rated, 'nullable', 'numeric', 'gt:0', 'lte:99.99'],
            'other_rate' => ['nullable', 'numeric', 'gte:0', 'lte:99.99'],
        ];
    }
}
