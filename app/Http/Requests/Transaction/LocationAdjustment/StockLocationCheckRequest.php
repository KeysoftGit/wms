<?php

namespace App\Http\Requests\Transaction\LocationAdjustment;

use Illuminate\Foundation\Http\FormRequest;

class StockLocationCheckRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'physical_warehouse_id' => 'required|string|exists:sqlsrv.Ms_Warehouse,WarehouseID',
            'part_id' => 'required|string|exists:sqlsrv.Ms_Part,PartID',
            'batch_no' => 'nullable|string',
            'unit_id' => 'nullable|string',
        ];
    }
}
