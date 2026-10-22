<?php

namespace App\Exceptions;

use App\Enums\ResponseCode;
use RuntimeException;

class BusinessException extends RuntimeException
{
    public function __construct(
        ResponseCode $code,
        ?string $msg = null,
        public mixed $data = null,
    ) {
        parent::__construct($msg ?? $code->message(), $code->value);
    }

    public function responseCode(): ResponseCode
    {
        return ResponseCode::from($this->code);
    }
}
