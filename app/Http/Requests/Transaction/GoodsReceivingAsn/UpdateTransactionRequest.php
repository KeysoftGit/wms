<?php

namespace App\Http\Requests\Transaction\GoodsReceivingAsn;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $merge = [];
        if ($this->has('transaction_no')) {
            $merge['transaction_no'] = trim((string) $this->input('transaction_no'));
        }
        if ($this->has('warehouse_id')) {
            $merge['warehouse_id'] = trim((string) $this->input('warehouse_id'));
        }
        if (!empty($merge)) {
            $this->merge($merge);
        }
    }

    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator): void
    {
        $message = $validator->errors()->first();
        \Illuminate\Support\Facades\Log::error('Validation failed for UpdateTransactionRequest: ' . $message, [
            'input' => $this->all(),
            'errors' => $validator->errors()->toArray(),
        ]);

        throw new \Illuminate\Http\Exceptions\HttpResponseException(
            \App\Helpers\ResponseFormatter::error($message, \App\Enums\StatusCodeEnum::BAD_REQUEST)->toResponse()
        );
    }

    public function rules(): array
    {
        return [
            'transaction_no' => 'required|string',
            'transaction_date' => 'required|date',
            'warehouse_id' => 'required|string',
            'notes' => 'nullable|string',
            'details' => 'required|array|min:1',
            'details.*.source_detail_key' => 'nullable|string',
            'details.*.part_id' => 'required|string',
            'details.*.sequence' => 'required|integer',
            'details.*.unit_id' => 'nullable|string',
            'details.*.qty_remaining' => 'nullable|numeric|min:0',
            'details.*.qty_receive' => 'required|numeric|min:0.000001',
            'details.*.batch_no' => 'nullable|string',
            'details.*.rev' => 'nullable|array',
        ];
    }
}
