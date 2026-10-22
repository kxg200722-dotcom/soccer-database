<?php

namespace App\Http\Controllers\Admin\Live;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Live\FileUploadRequest;
use App\Models\MatchFile;
use App\Models\MatchInfo;
use App\Services\Admin\Live\MatchFileService;
use App\Support\Result;
use Illuminate\Http\Request;

class MatchFileController extends Controller
{
    public function __construct(private MatchFileService $service)
    {
    }

    public function index(MatchInfo $match)
    {
        return Result::ok($this->service->listForMatch($match))->toResponse();
    }

    public function store(FileUploadRequest $request, MatchInfo $match)
    {
        $file = $this->service->upload(
            $match,
            $request->input('type'),
            $request->file('file'),
            $request->user()->id,
        );

        return Result::ok($file)->toResponse();
    }

    public function download(MatchFile $file)
    {
        return $this->service->download($file);
    }

    public function destroy(Request $request, MatchFile $file)
    {
        $this->service->delete($file, $request->user()->id);

        return Result::ok()->toResponse();
    }
}
