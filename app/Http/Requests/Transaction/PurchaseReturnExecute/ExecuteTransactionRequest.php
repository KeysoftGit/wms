<?php

namespace App\Http\Requests\Transaction\PurchaseReturnExecute;

use Illuminate\Foundation\Http\FormRequest;

class ExecuteTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'transaction_no' => 'required|string',
        ];
    }
}
