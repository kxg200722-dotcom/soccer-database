<?php

namespace App\Http\Requests\Admin\Live;

use Illuminate\Foundation\Http\FormRequest;

class MatchStoreRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'group_type' => ['required', 'in:university,middle_school,primary_school'],
            'home_team_name' => ['required', 'string', 'max:100'],
            'away_team_name' => ['required', 'string', 'max:100'],
            'home_team_logo' => ['nullable', 'string', 'max:255'],
            'away_team_logo' => ['nullable', 'string', 'max:255'],
            'match_time' => ['required', 'date'],
            'venue' => ['nullable', 'string', 'max:200'],
        ];
    }
}
