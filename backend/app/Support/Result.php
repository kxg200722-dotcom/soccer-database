<?php

namespace App\Support;

use App\Enums\ResponseCode;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\JsonResponse;

/**
 * 统一 API 响应体：{code, msg, data, success, trace_id}
 */
class Result implements Arrayable
{
    public function __construct(
        public int $code,
        public string $msg,
        public mixed $data = null,
    ) {
    }

    public static function ok(mixed $data = null, string $msg = 'success'): self
    {
        return new self(ResponseCode::SUCCESS->value, $msg, $data);
    }

    public static function error(ResponseCode $code, ?string $msg = null, mixed $data = null): self
    {
        return new self($code->value, $msg ?? $code->message(), $data);
    }

    public static function errorCode(int $code, string $msg, mixed $data = null): self
    {
        return new self($code, $msg, $data);
    }

    public function isSuccess(): bool
    {
        return $this->code === ResponseCode::SUCCESS->value;
    }

    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'msg' => $this->msg,
            'data' => $this->data,
            'success' => $this->isSuccess(),
            'trace_id' => request()->header('X-Trace-Id') ?? '',
        ];
    }

    public function toResponse(): JsonResponse
    {
        return response()->json($this->toArray());
    }
}
