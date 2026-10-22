<?php

namespace App\Http\Controllers\Admin\Live;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Live\LineupRequest;
use App\Http\Requests\Admin\Live\MatchStatusRequest;
use App\Http\Requests\Admin\Live\MatchStoreRequest;
use App\Http\Requests\Admin\Live\MatchUpdateRequest;
use App\Http\Resources\Live\AdminMatchDetailResource;
use App\Http\Resources\Live\MatchListResource;
use App\Models\MatchInfo;
use App\Services\Admin\Live\MatchService;
use App\Support\Result;
use Illuminate\Http\Request;

class MatchController extends Controller
{
    public function __construct(private MatchService $service)
    {
    }

    public function index(Request $request)
    {
        $request->validate([
            'group_type' => ['nullable', 'in:university,middle_school,primary_school'],
            'status' => ['nullable', 'in:not_started,live,finished'],
            'keyword' => ['nullable', 'string', 'max:50'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $paginator = MatchInfo::query()
            ->when($request->query('group_type'), fn ($q, $v) => $q->where('group_type', $v))
            ->when($request->query('status'), fn ($q, $v) => $q->where('status', $v))
            ->when($request->query('keyword'), fn ($q, $v) => $q->where(function ($qq) use ($v) {
                $qq->where('home_team_name', 'like', "%{$v}%")
                    ->orWhere('away_team_name', 'like', "%{$v}%");
            }))
            ->orderByDesc('match_time')
            ->orderByDesc('id')
            ->simplePaginate(15);

        return Result::ok([
            'list' => MatchListResource::collection($paginator->items())->resolve(),
            'next_page' => $paginator->hasMorePages() ? $paginator->currentPage() + 1 : null,
        ])->toResponse();
    }

    public function store(MatchStoreRequest $request)
    {
        $match = $this->service->create($request->validated(), $request->user()->id);

        return Result::ok(new AdminMatchDetailResource($match))->toResponse();
    }

    public function show(MatchInfo $match)
    {
        $match->load(['referees' => fn ($q) => $q->orderBy('id')]);

        return Result::ok(new AdminMatchDetailResource($match))->toResponse();
    }

    public function update(MatchUpdateRequest $request, MatchInfo $match)
    {
        $data = $request->safe()->except('version');
        $match = $this->service->update($match, $data, (int) $request->input('version'), $request->user()->id);

        return Result::ok(new AdminMatchDetailResource($match->loadMissing('referees')))->toResponse();
    }

    public function destroy(Request $request, MatchInfo $match)
    {
        $this->service->delete($match, $request->user()->id);

        return Result::ok()->toResponse();
    }

    public function changeStatus(MatchStatusRequest $request, MatchInfo $match)
    {
        $match = $this->service->changeStatus(
            $match,
            $request->input('action'),
            (int) $request->input('version'),
            $request->user()->id,
        );

        return Result::ok(new AdminMatchDetailResource($match))->toResponse();
    }

    public function updateLineup(LineupRequest $request, MatchInfo $match)
    {
        $match = $this->service->updateLineup(
            $match,
            $request->input('lineup_home'),
            $request->input('lineup_away'),
            (int) $request->input('version'),
            $request->user()->id,
        );

        return Result::ok(new AdminMatchDetailResource($match))->toResponse();
    }
}
