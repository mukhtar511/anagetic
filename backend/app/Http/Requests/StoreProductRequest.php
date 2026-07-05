<?php

namespace App\Http\Requests;

use App\Enums\ProductMode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string'],
            'category' => ['required', 'string'],
            'description' => ['nullable', 'string'],
            'condition' => ['nullable', 'string'],
            'code' => ['nullable', 'string'],
            'collection' => ['nullable', 'string'],
            'region_id' => ['nullable', 'integer', 'exists:regions,id'],
            'status' => ['nullable', 'string', 'in:live,out,draft'],
            'is_single_piece' => ['nullable', 'boolean'],
            'images' => ['nullable', 'array'],
            'original_price' => ['nullable', 'numeric'],
            'return_policy_ack' => ['nullable', 'boolean'],

            'modes' => ['required', 'array', 'min:1'],
            'modes.*.type' => ['required', Rule::enum(ProductMode::class)],
            'modes.*.price' => ['required', 'numeric', 'min:0'],
            'modes.*.deposit' => ['nullable', 'numeric', 'min:0'],
            'modes.*.prep_time' => ['nullable', 'string'],
            'modes.*.exec_time' => ['nullable', 'string'],
            'modes.*.rent_scope' => ['nullable', 'string'],

            'colors' => ['nullable', 'array'],
            'colors.*.name' => ['required_with:colors', 'string'],
            'colors.*.hex' => ['nullable', 'string'],
            'colors.*.qty' => ['nullable', 'integer', 'min:0'],

            'addons' => ['nullable', 'array'],
            'addons.*.name' => ['required_with:addons', 'string'],
            'addons.*.price' => ['required_with:addons', 'numeric', 'min:0'],
        ];
    }
}
