<?php

namespace App\Enums;

enum GroupType: string
{
    case UNIVERSITY = 'university';
    case MIDDLE_SCHOOL = 'middle_school';
    case PRIMARY_SCHOOL = 'primary_school';

    public function label(): string
    {
        return match ($this) {
            self::UNIVERSITY => '大学组',
            self::MIDDLE_SCHOOL => '中学组',
            self::PRIMARY_SCHOOL => '小学组',
        };
    }
}
