<?php

namespace App\Http\Requests\Transaction\GoodsReceivingAsn;

use Illuminate\Foundation\Http\FormRequest;

class StoreTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator): void
    {
        $message = $validator->errors()->first();
        \Illuminate\Support\Facades\Log::error('Validation failed for StoreTransactionRequest: ' . $message, [
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
            'transaction_no' => 'nullable|string|max:50|unique:\App\Models\TransGoodsReceivingHD,TransactionNo',
            'is_auto' => 'nullable|boolean',
            'transaction_date' => 'required|date',
            'asn_no' => 'required|string',
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

    public function messages(): array
    {
        return [
            'transaction_no.unique' => 'Transaction No has already been taken!',
        ];
    }
}
