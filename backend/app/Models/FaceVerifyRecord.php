<?php

namespace App\Models;

use App\Enums\VerifyResult;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FaceVerifyRecord extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'match_id', 'player_name', 'player_no', 'photo_path',
        'result', 'score', 'channel', 'request_id', 'remark', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'result' => VerifyResult::class,
            'score' => 'decimal:2',
        ];
    }

    public function match(): BelongsTo
    {
        return $this->belongsTo(MatchInfo::class, 'match_id');
    }
}
