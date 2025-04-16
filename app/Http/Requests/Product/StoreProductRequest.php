<?php

namespace App\Http\Requests\Product;

use App\Rules\ValidProductPrice;
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
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('products')->whereNull('deleted_at')
            ],
            'description' => 'nullable|string',
            'price' => [
                'required',
                'numeric',
                'min:0',
                new ValidProductPrice
            ],
            'stock' => 'required|integer|min:0',
            'image' => 'nullable|image|max:2048|mimes:jpeg,png,jpg,gif',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Название товара обязательно для заполнения',
            'name.unique' => 'Товар с таким названием уже существует',
            'price.min' => 'Цена не может быть отрицательной',
            'image.mimes' => 'Изображение должно быть в формате: jpeg, png, jpg или gif',
        ];
    }
}
