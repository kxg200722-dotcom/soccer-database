<?php

namespace App\Services\Public\Live;

use App\Enums\MatchStatus;
use App\Http\Resources\Live\MatchDetailResource;
use App\Http\Resources\Live\MatchListResource;
use App\Models\MatchEvent;
use App\Models\MatchInfo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * 公众端只读查询：live 列表缓存 30s，详情缓存 10s（与前端轮询同节拍）。
 * file 驱动无 tag，用 list_version 版本号实现列表批量失效（禁通配扫描）。
 * 注意：缓存最终渲染数组，不缓存模型对象（文件缓存反序列化模型不可靠）。
 */
class LiveQueryService
{
    public const DETAIL_TTL = 10;

    public const LIST_TTL = 30;

    public const CACHE_DETAIL_KEY = 'live:detail:';

    private const LIST_VERSION_KEY = 'live:list_version';

    public function paginateMatches(?string $groupType, ?string $status, int $page): array
    {
        // 进行中的比赛变化频繁，走短 TTL 缓存；其余直查 DB
        $useCache = $status === MatchStatus::LIVE->value;

        $query = function () use ($groupType, $status, $page) {
            $paginator = MatchInfo::query()
                ->select(['id', 'group_type', 'home_team_name', 'away_team_name', 'home_team_logo', 'away_team_logo', 'match_time', 'venue', 'status', 'home_score', 'away_score'])
                ->when($groupType, fn ($q) => $q->where('group_type', $groupType))
                ->when($status, fn ($q) => $q->where('status', $status))
                ->orderByDesc('match_time')
                ->simplePaginate(15, ['*'], 'page', $page);

            return [
                'list' => MatchListResource::collection($paginator->items())->resolve(),
                'next_page' => $paginator->hasMorePages() ? $paginator->currentPage() + 1 : null,
            ];
        };

        if (! $useCache) {
            return $query();
        }

        $version = Cache::get(self::LIST_VERSION_KEY, 1);
        $key = sprintf('live:list:v%s:%s:%s:%s', $version, $groupType ?? 'all', $status, $page);

        return Cache::remember($key, self::LIST_TTL, $query);
    }

    public function getMatchDetail(int $id): array
    {
        return Cache::remember(self::CACHE_DETAIL_KEY.$id, self::DETAIL_TTL, function () use ($id) {
            $match = MatchInfo::query()
                ->with(['referees' => fn ($q) => $q->orderBy('id')])
                ->findOrFail($id);

            return (new MatchDetailResource($match))->resolve();
        });
    }

    /**
     * @return Collection<int, MatchEvent>
     */
    public function getEventsSince(int $matchId, int $sinceId): Collection
    {
        return MatchEvent::query()
            ->where('match_id', $matchId)
            ->when($sinceId > 0, fn ($q) => $q->where('id', '>', $sinceId))
            ->orderBy('minute')
            ->orderBy('id')
            ->limit(200)
            ->get();
    }

    /** 详情缓存精确失效 */
    public static function forgetDetail(int $id): void
    {
        Cache::forget(self::CACHE_DETAIL_KEY.$id);
    }

    /** 列表批量失效：版本号 +1 使全部旧 key 作废 */
    public static function bumpListVersion(): void
    {
        $version = (int) Cache::get(self::LIST_VERSION_KEY, 1);
        Cache::forever(self::LIST_VERSION_KEY, $version + 1);
    }
}
