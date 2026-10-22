<?php

namespace App\Http\Requests\Admin\Live;

use Illuminate\Foundation\Http\FormRequest;

class MatchStatusRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'action' => ['required', 'in:start,finish'],
            'version' => ['required', 'integer', 'min:0'],
        ];
    }
}
