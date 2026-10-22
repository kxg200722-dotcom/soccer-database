<?php

namespace App\Models;

use App\Enums\MatchFileType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MatchFile extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'match_id', 'type', 'file_name', 'file_path',
        'mime', 'size', 'uploaded_by',
    ];

    protected function casts(): array
    {
        return ['type' => MatchFileType::class];
    }

    public function match(): BelongsTo
    {
        return $this->belongsTo(MatchInfo::class, 'match_id');
    }
}
