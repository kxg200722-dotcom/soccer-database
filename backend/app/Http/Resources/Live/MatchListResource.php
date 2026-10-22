<?php

namespace App\Http\Resources\Live;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MatchListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'group_type' => $this->group_type->value,
            'group_type_label' => $this->group_type->label(),
            'home_team_name' => $this->home_team_name,
            'away_team_name' => $this->away_team_name,
            'home_team_logo' => $this->home_team_logo,
            'away_team_logo' => $this->away_team_logo,
            'match_time' => $this->match_time?->toDateTimeString(),
            'venue' => $this->venue,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'home_score' => $this->home_score,
            'away_score' => $this->away_score,
        ];
    }
}
