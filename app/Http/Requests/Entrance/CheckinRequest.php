<?php

namespace App\Http\Requests\Entrance;

use Illuminate\Foundation\Http\FormRequest;

class CheckinRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'token' => ['required', 'string', 'max:512'],
            'validation_id' => ['required', 'string'],
        ];
    }
}
