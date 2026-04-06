<?php

namespace App\Http\Requests\Entrance;

use Illuminate\Foundation\Http\FormRequest;

class ValidateTokenRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'token' => ['required', 'string', 'max:512'],
        ];
    }
}
