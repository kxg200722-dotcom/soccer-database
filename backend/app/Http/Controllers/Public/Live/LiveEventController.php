<?php

namespace App\Http\Controllers\Public\Live;

use App\Http\Controllers\Controller;
use App\Http\Resources\Live\MatchEventResource;
use App\Services\Public\Live\LiveQueryService;
use App\Support\Result;
use Illuminate\Http\Request;

class LiveEventController extends Controller
{
    public function __construct(private LiveQueryService $service)
    {
    }

    public function index(Request $request, int $matchId)
    {
        $request->validate([
            'since_id' => ['nullable', 'integer', 'min:0'],
        ]);

        // 确认比赛存在（复用详情缓存，无额外 SQL）
        $this->service->getMatchDetail($matchId);

        $events = $this->service->getEventsSince($matchId, (int) $request->query('since_id', 0));

        return Result::ok(MatchEventResource::collection($events))->toResponse();
    }
}
