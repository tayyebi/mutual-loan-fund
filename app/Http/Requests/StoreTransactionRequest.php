<?php

namespace App\Http\Requests;

use App\Models\Transaction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Submitting a financial event. Nothing here moves money: a submitted
 * transaction is a claim until an administrator verifies it.
 */
class StoreTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Transaction::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', 'in:'.implode(',', [
                Transaction::TYPE_CONTRIBUTION,
                Transaction::TYPE_LOAN_REPAYMENT,
                Transaction::TYPE_TREASURY_TRANSFER,
                Transaction::TYPE_TREASURY_EXCHANGE,
                Transaction::TYPE_FEE,
            ])],
            'treasury_id' => ['required', 'integer'],
            'counter_treasury_id' => ['nullable', 'integer', 'different:treasury_id'],
            'loan_id' => ['nullable', 'integer'],
            'amount' => ['required', 'string', 'regex:/^\d+(\.\d+)?$/'],
            'counter_amount' => ['nullable', 'string', 'regex:/^\d+(\.\d+)?$/'],
            'fee_amount' => ['nullable', 'string', 'regex:/^\d+(\.\d+)?$/'],
            'occurred_on' => ['required', 'date', 'before_or_equal:today'],
            'reference' => ['nullable', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:500'],
            'tx_hash' => ['nullable', 'string', 'max:120'],
            'from_address' => ['nullable', 'string', 'max:120'],
            'receipt' => [
                'nullable',
                'file',
                'mimes:'.implode(',', (array) config('fund.receipts.mimes')),
                'max:'.config('fund.receipts.max_kilobytes'),
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $type = $this->input('type');

            if (in_array($type, [Transaction::TYPE_TREASURY_TRANSFER, Transaction::TYPE_TREASURY_EXCHANGE], true)
                && ! $this->filled('counter_treasury_id')) {
                $validator->errors()->add('counter_treasury_id', 'Choose the destination treasury.');
            }

            if ($type === Transaction::TYPE_TREASURY_EXCHANGE && ! $this->filled('counter_amount')) {
                $validator->errors()->add('counter_amount', 'Enter the amount received in the destination treasury.');
            }

            if ($type === Transaction::TYPE_LOAN_REPAYMENT && ! $this->filled('loan_id')) {
                $validator->errors()->add('loan_id', 'Choose the loan being repaid.');
            }
        });
    }
}
