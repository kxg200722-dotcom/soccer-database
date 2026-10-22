<?php

return [
    // 人脸核验驱动：mock | 未来接入第三方时新增实现并在此切换
    'driver' => env('FACEVERIFY_DRIVER', 'mock'),

    // 第三方接口超时时间（秒），超时映射 50008
    'timeout' => (int) env('FACEVERIFY_TIMEOUT', 3),
];
