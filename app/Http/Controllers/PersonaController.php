<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessCommentsJob;
use App\Services\CommentAnalysisService;
use Illuminate\Http\JsonResponse;

class PersonaController extends Controller
{
    public function __invoke(CommentAnalysisService $analyzer): JsonResponse
    {
        ProcessCommentsJob::dispatch();

        return response()->json([]);
    }
}
