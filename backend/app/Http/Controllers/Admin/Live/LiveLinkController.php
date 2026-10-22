<?php

namespace App\Http\Controllers\Admin\Live;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Live\LiveLinkRequest;
use App\Models\MatchInfo;
use App\Services\Admin\Live\LiveLinkService;
use App\Support\Result;

class LiveLinkController extends Controller
{
    public function __construct(private LiveLinkService $service)
    {
    }

    public function update(LiveLinkRequest $request, MatchInfo $match)
    {
        $match = $this->service->updateLink($match, $request->input('live_link'), $request->user()->id);

        return Result::ok(['live_link' => $match->live_link, 'version' => $match->version])->toResponse();
    }
}
