<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSmartRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'in:buy,rent,both,custom'],
            'description' => ['required', 'string'],
            'category' => ['required', 'string'],
            'size' => ['nullable', 'string'],
            'budget' => ['required', 'numeric', 'min:0'],
            'need_by' => ['nullable', 'date'],
            'scope' => ['nullable', 'string', 'in:region_first,region_only,all'],
            'ref_images' => ['nullable', 'array'],
            'change_notes' => ['nullable', 'string'],
        ];
    }
}
