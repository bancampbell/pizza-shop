<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

class StoreOrderRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'phone' => 'required|string|max:20',
            'email' => 'required|email|max:255',
            'address' => 'required|string|max:500',
            'delivery_time' => 'required|date|after:now'
        ];
    }

    public function messages()
    {
        return [
            'phone.required' => 'Телефон обязателен для заполнения',
            'phone.regex' => 'Неверный формат телефона',
            'email.required' => 'Email обязателен для заполнения',
            'email.email' => 'Неверный формат email',
            'address.required' => 'Адрес обязателен для заполнения',
            'delivery_time.after' => 'Время доставки должно быть в будущем',
        ];
    }
}
