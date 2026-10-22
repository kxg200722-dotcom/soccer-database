<?php

namespace App\Models;

use App\Enums\EventType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MatchEvent extends Model
{
    protected $fillable = [
        'match_id', 'type', 'minute', 'team_side',
        'player_name', 'player_no', 'related_player_name',
        'content', 'client_token', 'created_by',
    ];

    protected function casts(): array
    {
        return ['type' => EventType::class];
    }

    public function match(): BelongsTo
    {
        return $this->belongsTo(MatchInfo::class, 'match_id');
    }
}
