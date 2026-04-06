<?php

namespace App\Http\Requests\Entrance;

use Illuminate\Foundation\Http\FormRequest;

class OverrideRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'token' => ['required', 'string', 'max:512'],
            'validation_id' => ['required', 'string'],
            'reason' => ['required', 'string', 'min:10', 'max:500'],
        ];
    }
}
