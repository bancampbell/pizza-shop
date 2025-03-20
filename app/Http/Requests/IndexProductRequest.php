<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IndexProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'per_page' => 'nullable|integer|min:1|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'per_page.integer' => 'Параметр per_page должен быть целым числом.',
            'per_page.min' => 'Параметр per_page должен быть не меньше 1.',
            'per_page.max' => 'Параметр per_page должен быть не больше 100.',
        ];
    }
    public function attributes(): array
    {
        return [
            'per_page' => 'Количество элементов на странице',
        ];
    }
}
