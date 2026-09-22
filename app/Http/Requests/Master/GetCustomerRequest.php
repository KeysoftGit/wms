<?php

namespace App\Http\Requests\Master;

use Illuminate\Foundation\Http\FormRequest;

class GetCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'term' => 'nullable|string',
            'is_active' => 'required|boolean',
            'filter_by_salesman' => 'nullable|boolean',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ];
    }
}
