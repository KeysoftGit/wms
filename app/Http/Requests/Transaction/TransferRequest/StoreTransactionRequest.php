<?php

namespace App\Http\Requests\Transaction\TransferRequest;

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
            'transaction_no' => 'nullable|string|max:50|unique:sqlsrv.Trans_ItemTransferRequestHD,TransactionNo',
            'transaction_date' => 'required|date',
            'expired_date' => 'nullable|date',
            'warehouse_id_from' => 'required|string|exists:sqlsrv.Ms_Warehouse,WarehouseID',
            'warehouse_id_to' => 'required|string|exists:sqlsrv.Ms_Warehouse,WarehouseID',
            'staff_id_from' => 'required|string|exists:sqlsrv.Ms_Employee,EmployeeID',
            'staff_id_to' => 'required|string|exists:sqlsrv.Ms_Employee,EmployeeID',
            'need_for' => 'nullable|string',
            'details' => 'required|array|min:1',
            'details.*.part_id' => 'required|string|exists:sqlsrv.Ms_Part,PartID',
            'details.*.unit_id' => 'required|string|exists:sqlsrv.Ms_Unit,UnitID',
            'details.*.qty' => 'required|numeric|min:0',
        ];
    }
}
