<?php

namespace App\Http\Requests\Product;

use App\Rules\ValidProductPrice;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('products')
                    ->ignore($this->product)
                    ->whereNull('deleted_at'),
            ],
            'description' => 'nullable|string',
            'price' => [
                'sometimes',
                'numeric',
                'min:0',
                new ValidProductPrice(),
            ],
            'stock' => 'sometimes|integer|min:0',
            'image' => 'nullable|image|max:2048|mimes:jpeg,png,jpg,gif',
        ];
    }
}
