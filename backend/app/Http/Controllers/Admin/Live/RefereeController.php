<?php

namespace App\Http\Controllers\Admin\Live;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Live\RefereesRequest;
use App\Models\MatchInfo;
use App\Services\Admin\Live\RefereeService;
use App\Support\Result;

class RefereeController extends Controller
{
    public function __construct(private RefereeService $service)
    {
    }

    public function index(MatchInfo $match)
    {
        return Result::ok($this->service->listForMatch($match))->toResponse();
    }

    public function replace(RefereesRequest $request, MatchInfo $match)
    {
        $list = $this->service->replaceForMatch($match, $request->input('referees'), $request->user()->id);

        return Result::ok($list)->toResponse();
    }
}
