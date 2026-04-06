<?php

namespace App\Http\Requests\Entrance;

use Illuminate\Foundation\Http\FormRequest;

class ConfirmPaymentRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'token' => ['required', 'string', 'max:512'],
            'validation_id' => ['required', 'string'],
            'payment_method' => ['required', 'string', 'in:cash,card'],
            'amount' => ['required', 'string', 'regex:/^\d+\.\d{2}$/'],
        ];
    }
}
