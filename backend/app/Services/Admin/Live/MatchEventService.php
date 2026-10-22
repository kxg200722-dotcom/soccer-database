<?php

namespace App\Services\Admin\Live;

use App\Enums\EventType;
use App\Enums\MatchStatus;
use App\Enums\ResponseCode;
use App\Exceptions\BusinessException;
use App\Models\MatchEvent;
use App\Models\MatchInfo;
use App\Services\Public\Live\LiveQueryService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * 事件子系统（事务核心）：
 * - addEvent：锁比赛 → finished 抛 40002 → client_token 幂等 40009 → 插事件 → goal 锁内联动比分
 * - updateEvent：type/team_side 归属变化先回退旧记分再按新值重算
 * - deleteEvent：goal 比分回退
 * 事务内禁任何 HTTP/文件 IO；缓存失效统一 afterCommit。
 */
class MatchEventService
{
    /** @return Collection<int, MatchEvent> */
    public function listForMatch(MatchInfo $match): Collection
    {
        return $match->events()->orderBy('minute')->orderBy('id')->get();
    }

    /** @return array{event: MatchEvent, match: MatchInfo} */
    public function addEvent(MatchInfo $match, array $data, int $userId): array
    {
        return DB::transaction(function () use ($match, $data, $userId) {
            $locked = MatchInfo::where('id', $match->id)->lockForUpdate()->firstOrFail();
            $this->ensureNotFinished($locked);

            // 防重复提交：锁内检查 + client_token 唯一索引双保险
            if (MatchEvent::where('client_token', $data['client_token'])->exists()) {
                throw new BusinessException(ResponseCode::DUPLICATE_SUBMIT);
            }

            $before = ['home' => $locked->home_score, 'away' => $locked->away_score];

            $event = $locked->events()->create([...$data, 'created_by' => $userId]);
            $this->applyScore($locked, $event->type, $event->team_side, 1);

            Log::channel('business')->info('match event added', [
                'event_id' => $event->id,
                'match_id' => $locked->id,
                'operator' => $userId,
                'type' => $event->type->value,
                'minute' => $event->minute,
                'team_side' => $event->team_side,
                'score_before' => $before,
                'score_after' => ['home' => $locked->home_score, 'away' => $locked->away_score],
            ]);

            $this->invalidateCacheAfterCommit($locked->id);

            return ['event' => $event, 'match' => $locked];
        });
    }

    /** @return array{event: MatchEvent, match: MatchInfo} */
    public function updateEvent(MatchEvent $event, array $data, int $userId): array
    {
        return DB::transaction(function () use ($event, $data, $userId) {
            $locked = MatchInfo::where('id', $event->match_id)->lockForUpdate()->firstOrFail();
            $this->ensureNotFinished($locked);

            $before = ['home' => $locked->home_score, 'away' => $locked->away_score];

            // 归属可能变化：先回退旧记分，更新后按新值重算
            $this->applyScore($locked, $event->type, $event->team_side, -1);
            $event->update($data);
            $this->applyScore($locked, $event->type, $event->team_side, 1);

            Log::channel('business')->info('match event updated', [
                'event_id' => $event->id,
                'match_id' => $locked->id,
                'operator' => $userId,
                'fields' => array_keys($data),
                'score_before' => $before,
                'score_after' => ['home' => $locked->home_score, 'away' => $locked->away_score],
            ]);

            $this->invalidateCacheAfterCommit($locked->id);

            return ['event' => $event, 'match' => $locked];
        });
    }

    /** @return array{match: MatchInfo} */
    public function deleteEvent(MatchEvent $event, int $userId): array
    {
        return DB::transaction(function () use ($event, $userId) {
            $locked = MatchInfo::where('id', $event->match_id)->lockForUpdate()->firstOrFail();
            $this->ensureNotFinished($locked);

            $before = ['home' => $locked->home_score, 'away' => $locked->away_score];

            $this->applyScore($locked, $event->type, $event->team_side, -1);
            $event->delete();

            Log::channel('business')->info('match event deleted', [
                'event_id' => $event->id,
                'match_id' => $locked->id,
                'operator' => $userId,
                'type' => $event->type->value,
                'score_before' => $before,
                'score_after' => ['home' => $locked->home_score, 'away' => $locked->away_score],
            ]);

            $this->invalidateCacheAfterCommit($locked->id);

            return ['match' => $locked];
        });
    }

    /** goal 事件按 team_side 锁内联动比分；比分变化同时 version+1 */
    private function applyScore(MatchInfo $match, EventType $type, ?string $teamSide, int $delta): void
    {
        if ($type !== EventType::GOAL || $teamSide === null) {
            return;
        }

        $column = $teamSide === 'home' ? 'home_score' : 'away_score';
        $match->$column = max(0, $match->$column + $delta);
        $match->version = $match->version + 1;
        $match->save();
    }

    private function ensureNotFinished(MatchInfo $match): void
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
