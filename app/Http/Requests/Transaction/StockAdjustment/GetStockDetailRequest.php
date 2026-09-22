<?php

namespace App\Http\Requests\Transaction\StockAdjustment;

use Illuminate\Foundation\Http\FormRequest;

class GetStockDetailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'part_id' => 'required|string|exists:sqlsrv.Ms_Part,PartID',
            'warehouse_id' => 'required|string|exists:sqlsrv.Ms_Warehouse,WarehouseID',
            'batch_no' => 'nullable|string',
            'transaction_date' => 'nullable|date',
            'preview_part' => 'nullable|boolean',
        ];
    }
}
