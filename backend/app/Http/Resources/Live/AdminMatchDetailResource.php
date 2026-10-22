<?php

namespace App\Http\Resources\Live;

use Illuminate\Http\Request;

/**
 * 管理端比赛详情：在公众端字段基础上附带 version（乐观锁）与审计字段
 */
class AdminMatchDetailResource extends MatchDetailResource
{
    public function toArray(Request $request): array
    {
        return [
            ...parent::toArray($request),
            'version' => $this->version,
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
