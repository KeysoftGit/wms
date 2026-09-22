<?php

namespace App\Http\Requests\Transaction\GoodsReceivingAsn;

use Illuminate\Foundation\Http\FormRequest;

class GetAsnDetailsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator): void
    {
        $message = $validator->errors()->first();
        \Illuminate\Support\Facades\Log::error('Validation failed for GetAsnDetailsRequest: ' . $message, [
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
            'asn_no' => 'required|string',
            'gr_no' => 'nullable|string',
        ];
    }
}
