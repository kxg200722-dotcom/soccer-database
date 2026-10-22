<?php

namespace App\Enums;

enum EventType: string
{
    case GOAL = 'goal';
    case YELLOW = 'yellow';
    case RED = 'red';
    case SUBSTITUTION = 'substitution';
    case TEXT = 'text';

    public function label(): string
    {
        return match ($this) {
            self::GOAL => '进球',
            self::YELLOW => '黄牌',
            self::RED => '红牌',
            self::SUBSTITUTION => '换人',
            self::TEXT => '图文',
        };
    }
}
