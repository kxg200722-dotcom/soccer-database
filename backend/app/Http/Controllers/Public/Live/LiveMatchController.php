<?php

namespace App\Http\Controllers\Public\Live;

use App\Http\Controllers\Controller;
use App\Services\Public\Live\LiveQueryService;
use App\Support\Result;
use Illuminate\Http\Request;

class LiveMatchController extends Controller
{
    public function __construct(private LiveQueryService $service)
    {
    }

    public function index(Request $request)
    {
        $request->validate([
            'group_type' => ['nullable', 'in:university,middle_school,primary_school'],
            'status' => ['nullable', 'in:not_started,live,finished'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $paginator = $this->service->paginateMatches(
            $request->query('group_type'),
            $request->query('status'),
            (int) $request->query('page', 1),
        );

        return Result::ok($paginator)->toResponse();
    }

    public function show(int $id)
    {
        return Result::ok($this->service->getMatchDetail($id))->toResponse();
    }
}
