<?php

namespace App\Http\Requests\Admin\Live;

use Illuminate\Foundation\Http\FormRequest;

class MatchUpdateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'version' => ['required', 'integer', 'min:0'],
            'group_type' => ['sometimes', 'in:university,middle_school,primary_school'],
            'home_team_name' => ['sometimes', 'string', 'max:100'],
            'away_team_name' => ['sometimes', 'string', 'max:100'],
            'home_team_logo' => ['nullable', 'string', 'max:255'],
            'away_team_logo' => ['nullable', 'string', 'max:255'],
            'match_time' => ['sometimes', 'date'],
            'venue' => ['nullable', 'string', 'max:200'],
        ];
    }
}
