<?php

namespace App\Enums;

enum VerifyResult: string
{
    case PASS = 'pass';
    case FAIL = 'fail';
    case PENDING = 'pending';

    public function label(): string
    {
        return match ($this) {
            self::PASS => '通过',
            self::FAIL => '未通过',
            self::PENDING => '待核验',
        };
    }
}
