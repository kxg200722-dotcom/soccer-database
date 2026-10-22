<?php

namespace App\Http\Controllers\Admin\Live;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Live\FaceVerifyRequest;
use App\Models\MatchInfo;
use App\Services\Admin\Live\FaceVerifyService;
use App\Support\Result;

class FaceVerifyController extends Controller
{
    public function __construct(private FaceVerifyService $service)
    {
    }

    public function verify(FaceVerifyRequest $request, MatchInfo $match)
    {
        $record = $this->service->verify(
            $match,
            $request->input('player_name'),
            $request->input('player_no') !== null ? (int) $request->input('player_no') : null,
            $request->file('photo'),
            $request->user()->id,
        );

        return Result::ok($record)->toResponse();
    }

    public function records(MatchInfo $match)
    {
        return Result::ok($this->service->listForMatch($match))->toResponse();
    }
}
