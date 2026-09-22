<?php

namespace App\Http\Requests\Transaction\LocationAdjustment;

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
            'transaction_no' => 'required|string|exists:sqlsrv.Trans_DirectItemTransferHD,TransactionNo',
        ];
    }
}
