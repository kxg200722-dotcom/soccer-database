<?php

namespace App\Enums;

enum RefereeRole: string
{
    case CHIEF = 'chief';
    case ASSISTANT = 'assistant';
    case FOURTH = 'fourth';

    public function label(): string
    {
        return match ($this) {
            self::CHIEF => '主裁判',
            self::ASSISTANT => '助理裁判',
            self::FOURTH => '第四官员',
        };
    }

    /** 每个角色允许的最大人数 */
    public function maxCount(): int
    {
        return match ($this) {
            self::CHIEF, self::FOURTH => 1,
            self::ASSISTANT => 2,
        };
    }
}
