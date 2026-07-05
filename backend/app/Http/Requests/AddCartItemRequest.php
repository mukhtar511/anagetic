<?php

namespace App\Http\Requests;

use App\Enums\ProductMode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AddCartItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'mode' => ['required', Rule::enum(ProductMode::class)],
            'color' => ['nullable', 'string'],
            'qty' => ['nullable', 'integer', 'min:1'],
            'addon_ids' => ['nullable', 'array'],
            'addon_ids.*' => ['integer'],
            'packaging' => ['nullable', 'string'],
            'measurements' => ['nullable', 'array'],
            'notes' => ['nullable', 'string'],
            'occasion_date' => ['nullable', 'date'],
        ];
    }
}
