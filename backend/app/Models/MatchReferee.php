<?php

namespace App\Models;

use App\Enums\RefereeRole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MatchReferee extends Model
{
    protected $fillable = ['match_id', 'role', 'name'];

    protected function casts(): array
    {
        return ['role' => RefereeRole::class];
    }

    public function match(): BelongsTo
    {
        return $this->belongsTo(MatchInfo::class, 'match_id');
    }
}
