<?php

namespace App\Http\Requests\Admin\Live;

use Illuminate\Foundation\Http\FormRequest;

class EventStoreRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'type' => ['required', 'in:goal,yellow,red,substitution,text'],
            'minute' => ['required', 'integer', 'min:0', 'max:130'],
            'team_side' => ['required_if:type,goal,yellow,red,substitution', 'nullable', 'in:home,away'],
            'player_name' => ['required_if:type,goal,yellow,red,substitution', 'nullable', 'string', 'max:50'],
            'player_no' => ['nullable', 'integer', 'min:0', 'max:99'],
            // 换人：related_player_name 为换上球员
            'related_player_name' => ['required_if:type,substitution', 'nullable', 'string', 'max:50'],
            'content' => ['required_if:type,text', 'nullable', 'string', 'max:500'],
            'client_token' => ['required', 'string', 'max:64'],
        ];
    }
}
