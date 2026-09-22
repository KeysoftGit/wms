<?php

namespace App\Http\Requests\Transaction\PartUsage;

use Illuminate\Foundation\Http\FormRequest;

class StoreTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'transaction_no' => 'nullable|string|max:50|unique:sqlsrv.Trans_PartUsageHD,TransactionNo',
            'transaction_date' => 'required|date',
            'expired_date' => 'nullable|date',
            'wo_number' => 'nullable|string',
            'division_id' => 'required|string|exists:sqlsrv.Ms_Division,DivisionID',
            'notes' => 'nullable|string',
            'details' => 'required|array|min:1',
            'details.*.part_id' => 'required|string|exists:sqlsrv.Ms_Part,PartID',
            'details.*.warehouse_id' => 'required|string|exists:sqlsrv.Ms_Warehouse,WarehouseID',
            'details.*.qty' => 'required|numeric',
            'details.*.batch_no' => 'nullable|string',
            'details.*.notes' => 'nullable|string',
        ];
    }
}
