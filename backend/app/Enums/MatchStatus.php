<?php

namespace App\Enums;

enum MatchStatus: string
{
    case NOT_STARTED = 'not_started';
    case LIVE = 'live';
    case FINISHED = 'finished';

    public function label(): string
    {
        return match ($this) {
            self::NOT_STARTED => '未开始',
            self::LIVE => '进行中',
            self::FINISHED => '已结束',
        };
    }
}
