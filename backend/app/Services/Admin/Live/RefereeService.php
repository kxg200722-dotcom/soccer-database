<?php

namespace App\Services\Admin\Live;

use App\Enums\MatchStatus;
use App\Enums\RefereeRole;
use App\Enums\ResponseCode;
use App\Exceptions\BusinessException;
use App\Models\MatchInfo;
use App\Services\Public\Live\LiveQueryService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * 裁判配置：整体替换（delete+insert），角色人数上限走 RefereeRole::maxCount()。
 */
class RefereeService
{
    public function listForMatch(MatchInfo $match): array
    {
        return $match->referees()->orderBy('id')->get()
            ->map(fn ($r) => [
                'id' => $r->id,
                'role' => $r->role->value,
                'role_label' => $r->role->label(),
                'name' => $r->name,
            ])->all();
    }

    /** @param array<int, array{role: string, name: string}> $referees */
    public function replaceForMatch(MatchInfo $match, array $referees, int $userId): array
    {
        if ($match->status === MatchStatus::FINISHED) {
            throw new BusinessException(ResponseCode::MATCH_FINISHED_LOCKED);
        }

        // 角色人数上限：chief/fourth ≤1、assistant ≤2
        $counts = array_count_values(array_column($referees, 'role'));
        foreach ($counts as $role => $count) {
            $roleEnum = RefereeRole::from($role);
            if ($count > $roleEnum->maxCount()) {
                throw new BusinessException(
                    ResponseCode::PARAM_INVALID,
                    sprintf('%s最多%d人', $roleEnum->label(), $roleEnum->maxCount()),
                );
            }
        }

        return DB::transaction(function () use ($match, $referees, $userId) {
            $before = $match->referees()->count();

            $match->referees()->delete();
            $match->referees()->createMany($referees);

            Log::channel('business')->info('match referees replaced', [
                'match_id' => $match->id,
                'operator' => $userId,
                'before_count' => $before,
                'after_count' => count($referees),
                'referees' => $referees,
            ]);

            DB::afterCommit(fn () => LiveQueryService::forgetDetail($match->id));

            return $this->listForMatch($match->refresh());
        });
    }
}
