<?php

namespace App\Http\Requests\Transaction\GoodsReceivingAsn;

use Illuminate\Foundation\Http\FormRequest;

class GetTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'term' => 'nullable|string',
            'warehouse_id' => 'nullable|string|exists:sqlsrv.Ms_Warehouse,WarehouseID',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'outstanding' => 'nullable|boolean',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ];
    }
}
