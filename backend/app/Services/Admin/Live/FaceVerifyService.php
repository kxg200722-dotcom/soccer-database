<?php

namespace App\Services\Admin\Live;

use App\Enums\ResponseCode;
use App\Enums\VerifyResult;
use App\Exceptions\BusinessException;
use App\Integrations\FaceVerify\FaceVerifyClient;
use App\Integrations\FaceVerify\FaceVerifyException;
use App\Models\FaceVerifyRecord;
use App\Models\MatchInfo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * 人脸核验：照片落盘 → 调 FaceVerifyClient（契约，当前 mock）→ 落库 channel/request_id/score。
 * 第三方超时/异常 → BusinessException(50008) 并写 exception 日志。
 */
class FaceVerifyService
{
    public function __construct(private FaceVerifyClient $client)
    {
    }

    public function verify(MatchInfo $match, string $playerName, ?int $playerNo, UploadedFile $photo, int $userId): array
    {
        $path = $photo->storeAs(
            sprintf('face-verify/%d', $match->id),
            Str::uuid().'.'.$photo->getClientOriginalExtension(),
            'local',
        );

        try {
            $result = $this->client->verify($path, $playerName);
        } catch (FaceVerifyException $e) {
            Log::channel('exception')->error('face verify failed', [
                'match_id' => $match->id,
                'player_name' => $playerName,
                'channel' => config('faceverify.driver'),
                'message' => $e->getMessage(),
            ]);

            throw new BusinessException(ResponseCode::FACE_VERIFY_FAILED, $e->getMessage());
        }

        $record = DB::transaction(function () use ($match, $playerName, $playerNo, $path, $result, $userId) {
            $record = $match->faceVerifyRecords()->create([
                'player_name' => $playerName,
                'player_no' => $playerNo,
                'photo_path' => $path,
                'result' => $result->passed ? VerifyResult::PASS : VerifyResult::FAIL,
                'score' => $result->score,
                'channel' => config('faceverify.driver'),
                'request_id' => $result->requestId,
                'created_by' => $userId,
            ]);

            Log::channel('business')->info('face verify recorded', [
                'record_id' => $record->id,
                'match_id' => $match->id,
                'operator' => $userId,
                'player_name' => $playerName,
                'result' => $record->result->value,
                'score' => $result->score,
                'request_id' => $result->requestId,
            ]);

            return $record;
        });

        return $this->toArray($record);
    }

    public function listForMatch(MatchInfo $match): array
    {
        return $match->faceVerifyRecords()->orderByDesc('id')->get()
            ->map(fn (FaceVerifyRecord $r) => $this->toArray($r))->all();
    }

    private function toArray(FaceVerifyRecord $r): array
    {
        return [
            'id' => $r->id,
            'player_name' => $r->player_name,
            'player_no' => $r->player_no,
            'result' => $r->result->value,
            'result_label' => $r->result->label(),
            'score' => (float) $r->score,
            'channel' => $r->channel,
            'request_id' => $r->request_id,
            'created_at' => $r->created_at?->toDateTimeString(),
        ];
    }
}
