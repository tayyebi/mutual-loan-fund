<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWalletRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Wallet::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'currency' => ['required', 'string', 'in:'.implode(',', array_keys((array) config('fund.currencies')))],
            'network' => ['required', 'string', 'in:'.implode(',', array_keys((array) config('fund.networks')))],
            'address' => ['required', 'string', 'max:120'],
            'label' => ['nullable', 'string', 'max:80'],
        ];
    }

    /**
     * The application is non-custodial. There is no field here for a private key
     * or seed phrase, and there never should be.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['address' => 'wallet address'];
    }
}
