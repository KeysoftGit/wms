<?php

namespace App\Http\Requests\Transaction\LocationAdjustment;

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
            'transaction_no' => 'nullable|string|max:50|unique:sqlsrv.Trans_DirectItemTransferHD,TransactionNo',
            'transaction_date' => 'required|date',
            'physical_warehouse_id' => 'required|string|exists:sqlsrv.Ms_Warehouse,WarehouseID',
            'staff_in_charge_id' => 'nullable|string',
            'notes' => 'nullable|string',
            'details' => 'required|array|min:1',
            'details.*.warehouse_id_from' => 'required|string|exists:sqlsrv.Ms_Warehouse,WarehouseID|different:physical_warehouse_id',
            'details.*.part_id' => 'required|string|exists:sqlsrv.Ms_Part,PartID',
            'details.*.unit_id' => 'required|string',
            'details.*.conversion' => 'required|numeric|min:0.000001',
            'details.*.qty' => 'required|numeric|min:0.000001',
            'details.*.dimension' => 'nullable|string',
            'details.*.cartoon_no' => 'nullable|string',
            'details.*.notes' => 'nullable|string',
            'details.*.batch_no' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'transaction_no.unique' => 'Transaction No has already been taken!',
            'details.*.warehouse_id_from.different' => 'Source warehouse must not be the same as physical warehouse!',
        ];
    }
}
