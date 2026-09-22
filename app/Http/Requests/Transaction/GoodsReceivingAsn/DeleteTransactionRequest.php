<?php

namespace App\Http\Requests\Transaction\GoodsReceivingAsn;

use Illuminate\Foundation\Http\FormRequest;

class DeleteTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('transaction_no')) {
            $this->merge([
                'transaction_no' => trim((string) $this->input('transaction_no')),
            ]);
        }
    }

    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator): void
    {
        $message = $validator->errors()->first();
        \Illuminate\Support\Facades\Log::error('Validation failed for DeleteTransactionRequest: ' . $message, [
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
            'transaction_no' => 'required|string|exists:\App\Models\TransGoodsReceivingHD,TransactionNo',
        ];
    }
}
