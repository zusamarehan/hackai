<?php

namespace App\Http\Controllers;

use App\Jobs\LinkedInScrapperJob;
use App\Jobs\ProcessCommentsJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class LinkedInController extends Controller
{
    CONST LINKEDIN = 'LINKEDIN';
    public function __invoke(Request $request): JsonResponse
    {
        try {
            $validator = $request->validate([
                'url' => ['required', 'url']
            ]);

            $linkedIn = "https://www.linkedin.com/posts/vitddnv_i-spent-all-yesterday-building-something-activity-7341809198910816257-caUC?utm_source=share&utm_medium=member_desktop&rcm=ACoAAB72h0MBOnUIO9cft0auMRaOXpe38GB28kU";

            LinkedInScrapperJob::dispatch($request->url);

            return response()->json([], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => "Something went wrong",
            ], 500);
        }
    }
}
