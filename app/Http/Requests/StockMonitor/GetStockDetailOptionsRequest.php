<?php

namespace App\Http\Requests\StockMonitor;

use Illuminate\Foundation\Http\FormRequest;

class GetStockDetailOptionsRequest extends FormRequest
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
            'target' => 'required|in:batch_no',
            'term' => 'nullable|string',
            'batch_no' => 'nullable|string',
            'filter_qty' => 'nullable|boolean',
        ];
    }
}
