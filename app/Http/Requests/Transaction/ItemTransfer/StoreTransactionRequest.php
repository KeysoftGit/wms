<?php

namespace App\Http\Requests\Transaction\ItemTransfer;

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
            'warehouse_id_from' => 'required|string|exists:sqlsrv.Ms_Warehouse,WarehouseID|different:warehouse_id_to',
            'staff_in_charge_id_from' => 'required|string|exists:sqlsrv.Ms_Employee,EmployeeID',
            'warehouse_id_to' => 'required|string|exists:sqlsrv.Ms_Warehouse,WarehouseID',
            'staff_in_charge_id_to' => 'required|string|exists:sqlsrv.Ms_Employee,EmployeeID',
            'notes' => 'nullable|string',
            'details' => 'required|array|min:1',
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
            'warehouse_id_from.different' => 'Target warehouse must not be the same as source warehouse!',
        ];
    }
}
