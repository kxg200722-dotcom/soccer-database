<?php

namespace App\Services\Admin\Live;

use App\Enums\MatchStatus;
use App\Enums\ResponseCode;
use App\Exceptions\BusinessException;
use App\Models\MatchInfo;
use App\Services\Public\Live\LiveQueryService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * 比赛管理：update/delete/changeStatus/updateLineup 均走事务+乐观锁+缓存失效。
 * 缓存失效用 DB::afterCommit 保证提交后再清。
 */
class MatchService
{
    public function create(array $data, int $userId): MatchInfo
    {
        $match = MatchInfo::create([
            ...$data,
            'status' => MatchStatus::NOT_STARTED->value,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        Log::channel('business')->info('match created', [
            'match_id' => $match->id,
            'operator' => $userId,
            'data' => $data,
        ]);

        LiveQueryService::bumpListVersion();

        return $match;
    }

    public function update(MatchInfo $match, array $data, int $version, int $userId): MatchInfo
    {
        $this->ensureEditable($match);

        return DB::transaction(function () use ($match, $data, $version, $userId) {
            $data['updated_by'] = $userId;
            $data['version'] = $version + 1;

            $affected = MatchInfo::where('id', $match->id)
                ->where('version', $version)
                ->update($data);

            if ($affected === 0) {
                throw new BusinessException(ResponseCode::OPTIMISTIC_LOCK_CONFLICT);
            }

            Log::channel('business')->info('match updated', [
                'match_id' => $match->id,
                'operator' => $userId,
                'fields' => array_keys($data),
            ]);

            $this->invalidateCacheAfterCommit($match->id);

            return $match->fresh();
        });
    }

    public function delete(MatchInfo $match, int $userId): void
    {
        if ($match->status !== MatchStatus::NOT_STARTED) {
            throw new BusinessException(ResponseCode::MATCH_FINISHED_LOCKED, '仅未开始的比赛可删除');
        }

        DB::transaction(function () use ($match, $userId) {
            $match->delete();

            Log::channel('business')->info('match deleted', [
                'match_id' => $match->id,
                'operator' => $userId,
            ]);

            $this->invalidateCacheAfterCommit($match->id);
        });
    }

    /**
     * 状态机：not_started→live→finished 单向流转
     */
    public function changeStatus(MatchInfo $match, string $action, int $version, int $userId): MatchInfo
    {
        $expected = [
            'start' => [MatchStatus::NOT_STARTED, MatchStatus::LIVE],
            'finish' => [MatchStatus::LIVE, MatchStatus::FINISHED],
        ];
        [$from, $to] = $expected[$action];

        return DB::transaction(function () use ($match, $action, $from, $to, $version, $userId) {
            if ($match->status !== $from) {
                throw new BusinessException(
                    ResponseCode::MATCH_FINISHED_LOCKED,
                    sprintf('当前状态[%s]不允许执行[%s]', $match->status->label(), $action),
                );
            }

            $before = $match->status->value;

            $affected = MatchInfo::where('id', $match->id)
                ->where('version', $version)
                ->update([
                    'status' => $to->value,
                    'version' => $version + 1,
                    'updated_by' => $userId,
                ]);

            if ($affected === 0) {
                throw new BusinessException(ResponseCode::OPTIMISTIC_LOCK_CONFLICT);
            }

            Log::channel('business')->info('match status changed', [
                'match_id' => $match->id,
                'operator' => $userId,
                'before' => $before,
                'after' => $to->value,
            ]);

            $this->invalidateCacheAfterCommit($match->id);

            return $match->fresh();
        });
    }

    public function updateLineup(MatchInfo $match, array $lineupHome, array $lineupAway, int $version, int $userId): MatchInfo
    {
        $this->ensureEditable($match);

        return DB::transaction(function () use ($match, $lineupHome, $lineupAway, $version, $userId) {
            $affected = MatchInfo::where('id', $match->id)
                ->where('version', $version)
                ->update([
                    'lineup_home' => json_encode($lineupHome, JSON_UNESCAPED_UNICODE),
                    'lineup_away' => json_encode($lineupAway, JSON_UNESCAPED_UNICODE),
                    'version' => $version + 1,
                    'updated_by' => $userId,
                ]);

            if ($affected === 0) {
                throw new BusinessException(ResponseCode::OPTIMISTIC_LOCK_CONFLICT);
            }

            Log::channel('business')->info('match lineup updated', [
                'match_id' => $match->id,
                'operator' => $userId,
                'home_count' => count($lineupHome),
                'away_count' => count($lineupAway),
            ]);

            $this->invalidateCacheAfterCommit($match->id);

            return $match->fresh();
        });
    }

    private function ensureEditable(MatchInfo $match): void
    {
        if ($match->status === MatchStatus::FINISHED) {
            throw new BusinessException(ResponseCode::MATCH_FINISHED_LOCKED);
        }
    }

    private function invalidateCacheAfterCommit(int $matchId): void
    {
        DB::afterCommit(function () use ($matchId) {
            LiveQueryService::forgetDetail($matchId);
            LiveQueryService::bumpListVersion();
        });
    }
}
