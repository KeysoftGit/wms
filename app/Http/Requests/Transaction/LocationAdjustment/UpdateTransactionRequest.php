<?php

namespace App\Http\Requests\Transaction\LocationAdjustment;

class UpdateTransactionRequest extends StoreTransactionRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        $rules['transaction_no'] = 'required|string|exists:sqlsrv.Trans_DirectItemTransferHD,TransactionNo';

        return $rules;
    }
}
