<?php

namespace App\Http\Requests\Transaction\StockAdjustment;

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
            'transaction_no' => 'nullable|string|max:50|unique:sqlsrv.Trans_InventoryAdjustmentHD,TransactionNo',
            'transaction_date' => 'required|date',
            'expired_date' => 'required|date',
            'warehouse_id' => 'required|string|exists:sqlsrv.Ms_Warehouse,WarehouseID',
            'division_id' => 'required|string|exists:sqlsrv.Ms_Division,DivisionID',
            'inventory_type_id' => 'nullable|string|exists:sqlsrv.Ms_InventoryType,InventoryTypeID',
            'notes' => 'nullable|string',
            'details' => 'required|array|min:1',
            'details.*.part_id' => 'required|string|exists:sqlsrv.Ms_Part,PartID',
            'details.*.unit_id' => 'nullable|string',
            'details.*.qty_opname' => 'required|numeric|min:0',
            'details.*.batch_no' => 'nullable|string',
            'checkers' => 'required|array|min:1',
            'checkers.*.employee_id' => 'required|string|exists:sqlsrv.Ms_Employee,EmployeeID',
            'checkers.*.status' => 'required|string',
        ];
    }
}
