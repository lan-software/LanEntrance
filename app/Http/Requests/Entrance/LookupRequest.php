<?php

namespace App\Http\Requests\Entrance;

use Illuminate\Foundation\Http\FormRequest;

class LookupRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'q' => ['required', 'string', 'min:2', 'max:100'],
        ];
    }
}
