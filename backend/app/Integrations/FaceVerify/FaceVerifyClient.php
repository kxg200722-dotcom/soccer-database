<?php

namespace App\Integrations\FaceVerify;

use App\Integrations\FaceVerify\DTO\VerifyResultDTO;

/**
 * 人脸核验客户端契约：第三方实现替换时业务零改动（见 ADR-002 与 config/faceverify.php）。
 */
interface FaceVerifyClient
{
    /**
     * @throws FaceVerifyException 超时/服务异常（业务层映射为 50008）
     */
    public function verify(string $photoPath, string $playerName): VerifyResultDTO;
}
