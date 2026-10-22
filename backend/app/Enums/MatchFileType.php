<?php

namespace App\Enums;

enum MatchFileType: string
{
    case SIGN_IN = 'sign_in';
    case RECORD_CONFIRM = 'record_confirm';
    case REFEREE_REPORT = 'referee_report';

    public function label(): string
    {
        return match ($this) {
            self::SIGN_IN => '签到表',
            self::RECORD_CONFIRM => '记录集签字确认表',
            self::REFEREE_REPORT => '裁判员报告书',
        };
    }
}
