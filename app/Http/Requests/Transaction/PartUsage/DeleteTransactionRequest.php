<?php

namespace App\Http\Requests\Transaction\PartUsage;

use Illuminate\Foundation\Http\FormRequest;

class DeleteTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'transaction_no' => 'required|string|exists:sqlsrv.Trans_PartUsageHD,TransactionNo',
        ];
    }
}
