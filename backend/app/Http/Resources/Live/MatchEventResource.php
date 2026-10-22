<?php

namespace App\Http\Resources\Live;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MatchEventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type->value,
            'type_label' => $this->type->label(),
            'minute' => $this->minute,
            'team_side' => $this->team_side,
            'player_name' => $this->player_name,
            'player_no' => $this->player_no,
            'related_player_name' => $this->related_player_name,
            'content' => $this->content,
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
