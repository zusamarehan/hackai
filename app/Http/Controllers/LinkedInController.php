<?php

namespace App\Http\Controllers;

use App\Jobs\LinkedInScrapperJob;
use App\Jobs\ProcessCommentsJob;

class LinkedInController extends Controller
{
    CONST LINKEDIN = 'LINKEDIN';
    public function __invoke(): array
    {
        $linkedIn = "https://www.linkedin.com/posts/vitddnv_i-spent-all-yesterday-building-something-activity-7341809198910816257-caUC?utm_source=share&utm_medium=member_desktop&rcm=ACoAAB72h0MBOnUIO9cft0auMRaOXpe38GB28kU";

        LinkedInScrapperJob::dispatch($linkedIn);

        return [];
    }
}
