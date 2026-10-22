<?php

namespace App\Models;

use App\Enums\GroupType;
use App\Enums\MatchStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 比赛信息（命名 MatchInfo 避开 PHP 8 保留字 match，见 ADR-001）
 */
class MatchInfo extends Model
{
    use SoftDeletes;

    protected $table = 'matches';

    protected $fillable = [
        'group_type', 'home_team_name', 'away_team_name',
        'home_team_logo', 'away_team_logo', 'match_time', 'venue',
        'status', 'home_score', 'away_score',
        'lineup_home', 'lineup_away', 'live_link', 'version',
        'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'group_type' => GroupType::class,
            'status' => MatchStatus::class,
            'match_time' => 'datetime',
            'lineup_home' => 'array',
            'lineup_away' => 'array',
        ];
    }

    public function referees(): HasMany
    {
        return $this->hasMany(MatchReferee::class, 'match_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(MatchEvent::class, 'match_id');
    }

    public function files(): HasMany
    {
        return $this->hasMany(MatchFile::class, 'match_id');
    }

    public function faceVerifyRecords(): HasMany
    {
        return $this->hasMany(FaceVerifyRecord::class, 'match_id');
    }
}
