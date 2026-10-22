<?php

namespace App\Integrations\FaceVerify;

use App\Integrations\FaceVerify\DTO\VerifyResultDTO;
use Illuminate\Support\Str;

/**
 * Mock 实现：默认高分通过；player_name 含 "timeout" 时模拟第三方超时（验收 50008 用）。
 */
class MockFaceVerifyClient implements FaceVerifyClient
{
    public function __construct(private int $timeout = 3)
    {
    }

    public function verify(string $photoPath, string $playerName): VerifyResultDTO
    {
        if (str_contains(strtolower($playerName), 'timeout')) {
            throw new FaceVerifyException(sprintf('face verify timeout after %ds', $this->timeout));
        }

        return new VerifyResultDTO(
            passed: true,
            score: 96.5,
            requestId: 'mock-'.Str::uuid()->toString(),
        );
    }
}
