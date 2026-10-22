<?php

namespace App\Integrations\FaceVerify\DTO;

readonly class VerifyResultDTO
{
    public function __construct(
        public bool $passed,
        public float $score,
        public string $requestId,
    ) {
    }
}
