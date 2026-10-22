<?php

namespace App\Http\Requests\Admin\Live;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 部分更新：未传 type 时仅做格式校验；传 type（变更类型）时按新类型强制必填项。
 * client_token 不可修改（不列入规则，validated() 自动丢弃）。
 */
class EventUpdateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'type' => ['sometimes', 'in:goal,yellow,red,substitution,text'],
            'minute' => ['sometimes', 'integer', 'min:0', 'max:130'],
            'team_side' => ['required_if:type,goal,yellow,red,substitution', 'nullable', 'in:home,away'],
            'player_name' => ['required_if:type,goal,yellow,red,substitution', 'nullable', 'string', 'max:50'],
            'player_no' => ['nullable', 'integer', 'min:0', 'max:99'],
            'related_player_name' => ['required_if:type,substitution', 'nullable', 'string', 'max:50'],
            'content' => ['required_if:type,text', 'nullable', 'string', 'max:500'],
        ];
    }
}
