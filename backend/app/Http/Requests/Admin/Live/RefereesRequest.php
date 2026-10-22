<?php

namespace App\Http\Requests\Admin\Live;

use Illuminate\Foundation\Http\FormRequest;

class RefereesRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'referees' => ['present', 'array', 'max:4'],
            'referees.*.role' => ['required', 'in:chief,assistant,fourth'],
            'referees.*.name' => ['required', 'string', 'max:50'],
        ];
    }
}
