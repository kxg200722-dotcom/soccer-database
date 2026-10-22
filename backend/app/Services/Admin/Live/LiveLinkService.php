<?php

namespace App\Services\Admin\Live;

use App\Enums\MatchStatus;
use App\Enums\ResponseCode;
use App\Exceptions\BusinessException;
use App\Models\MatchInfo;
use App\Services\Public\Live\LiveQueryService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LiveLinkService
{
    public function updateLink(MatchInfo $match, ?string $link, int $userId): MatchInfo
    {
        if ($match->status === MatchStatus::FINISHED) {
            throw new BusinessException(ResponseCode::MATCH_FINISHED_LOCKED);
        }

        return DB::transaction(function () use ($match, $link, $userId) {
            $before = $match->live_link;
            $match->live_link = $link;
            $match->version = $match->version + 1;
            $match->updated_by = $userId;
            $match->save();

            Log::channel('business')->info('match live link updated', [
                'match_id' => $match->id,
                'operator' => $userId,
                'before' => $before,
                'after' => $link,
            ]);

            DB::afterCommit(function () use ($match) {
                LiveQueryService::forgetDetail($match->id);
                LiveQueryService::bumpListVersion();
            });

            return $match;
        });
    }
}
