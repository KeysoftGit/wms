<?php

namespace App\Http\Requests\Transaction\PartUsage;

use Illuminate\Foundation\Http\FormRequest;

class GetTransactionDetailsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'transaction_no' => 'required|string|exists:sqlsrv.Trans_PartUsageHD,TransactionNo',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ];
    }
}
