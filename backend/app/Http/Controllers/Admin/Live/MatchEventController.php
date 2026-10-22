<?php

namespace App\Http\Controllers\Admin\Live;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Live\EventStoreRequest;
use App\Http\Requests\Admin\Live\EventUpdateRequest;
use App\Http\Resources\Live\MatchEventResource;
use App\Models\MatchEvent;
use App\Models\MatchInfo;
use App\Services\Admin\Live\MatchEventService;
use App\Support\Result;
use Illuminate\Http\Request;

class MatchEventController extends Controller
{
    public function __construct(private MatchEventService $service)
    {
    }

    public function index(MatchInfo $match)
    {
        $events = $this->service->listForMatch($match);

        return Result::ok(MatchEventResource::collection($events)->resolve())->toResponse();
    }

    public function store(EventStoreRequest $request, MatchInfo $match)
    {
        $result = $this->service->addEvent($match, $request->validated(), $request->user()->id);

        return Result::ok($this->payload($result))->toResponse();
    }

    public function update(EventUpdateRequest $request, MatchEvent $event)
    {
        $result = $this->service->updateEvent($event, $request->validated(), $request->user()->id);

        return Result::ok($this->payload($result))->toResponse();
    }

    public function destroy(Request $request, MatchEvent $event)
    {
        $result = $this->service->deleteEvent($event, $request->user()->id);

        return Result::ok($this->payload($result))->toResponse();
    }

    /** 事件 + 最新比分/版本号，前端用返回值局部更新，无需整页刷新 */
    private function payload(array $result): array
    {
        $data = [
            'home_score' => $result['match']->home_score,
            'away_score' => $result['match']->away_score,
            'version' => $result['match']->version,
        ];
        if (isset($result['event'])) {
            $data['event'] = (new MatchEventResource($result['event']))->resolve();
        }

        return $data;
    }
}
