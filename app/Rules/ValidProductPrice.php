<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidProductPrice implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Проверяем, что цена имеет не более 2 знаков после запятой
        if (preg_match('/\.\d{3,}/', (string)$value)) {
            $fail('Цена должна содержать не более 2 знаков после запятой.');
        }

        // Проверяем, что цена не превышает максимально допустимую
        if ($value > 1000000) {
            $fail('Цена товара не может превышать 1 000 000.');
        }

        // Дополнительная проверка для "красивых" цен (оканчивающихся на 99)
        if (config('app.enforce_99_pricing') && substr($value, -2) != '99') {
            $fail('Цена должна оканчиваться на .99 (например: 199.99)');
        }
    }
}
