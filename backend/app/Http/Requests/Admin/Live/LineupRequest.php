<?php

namespace App\Http\Requests\Admin\Live;

use Illuminate\Foundation\Http\FormRequest;

class LineupRequest extends FormRequest
{
    public function rules(): array
    {
        $player = [
            'name' => ['required', 'string', 'max:50'],
            'no' => ['required', 'string', 'max:16'],
            'position' => ['nullable', 'string', 'max:20'],
            'is_captain' => ['nullable', 'boolean'],
        ];

        return [
            'version' => ['required', 'integer', 'min:0'],
            'lineup_home' => ['present', 'array'],
            'lineup_away' => ['present', 'array'],
            'lineup_home.*.name' => $player['name'],
            'lineup_home.*.no' => $player['no'],
            'lineup_home.*.position' => $player['position'],
            'lineup_home.*.is_captain' => $player['is_captain'],
            'lineup_away.*.name' => $player['name'],
            'lineup_away.*.no' => $player['no'],
            'lineup_away.*.position' => $player['position'],
            'lineup_away.*.is_captain' => $player['is_captain'],
        ];
    }
}
