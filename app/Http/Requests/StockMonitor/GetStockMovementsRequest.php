<?php

namespace App\Http\Requests\StockMonitor;

use Illuminate\Foundation\Http\FormRequest;

class GetStockMovementsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'part_id' => 'required|string',
            'warehouse_id' => 'nullable|string',
            'include_children' => 'nullable|boolean',
            'unit_id' => 'nullable|string',
            'batch_no' => 'nullable|string',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ];
    }
}
