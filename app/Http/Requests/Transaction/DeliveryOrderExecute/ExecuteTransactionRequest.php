<?php

namespace App\Http\Requests\Transaction\DeliveryOrderExecute;

use Illuminate\Foundation\Http\FormRequest;

class ExecuteTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'transaction_no' => 'required|string',
            'vehicle_id' => 'required|string|max:100',
            'driver_id' => 'required|string|max:100',
            'details' => 'nullable|array',
            'details.*.sequence' => 'required|integer|distinct',
            'details.*.warehouse_id' => 'nullable|string',
        ];
    }
}
