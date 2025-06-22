<?php

namespace App\Jobs;

use App\Http\Controllers\LinkedInController;
use App\Services\SupabaseService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class LinkedInScrapperJob implements ShouldQueue
{
    use Queueable;

    public $linkedInURL;
    protected $supabase;

    /**
     * Create a new job instance.
     */
    public function __construct($linkedInURL)
    {
        $this->linkedInURL = $linkedInURL;
    }

    public function handle(): void
    {
        $this->supabase = new SupabaseService();

        $linedInScrapper = 'https://hackai-be.onrender.com/linkedin-full';

        $url = $this->linkedInURL;

        $response = Http::withHeaders([
            'x-api-key' => config('services.scrapper.key'),
        ])->post($linedInScrapper, [
            'url' => $url
        ]);

        if (! $response->successful()) {
            Log::error($response->getState());
        }

        $uuid = Str::uuid();

        $postId = $this->initiateAnalysis($uuid, $url);

        Storage::disk('public')->put("linkedin/$uuid.json", $response->body());

        ProcessCommentsJob::dispatch($postId, $uuid, LinkedInController::LINKEDIN);
    }

    public function initiateAnalysis($uuid, $url): string
    {
        $res = $this->supabase->insert('posts', [
            'url' => $url,
            'uuid' => $uuid,
            'type' => LinkedInController::LINKEDIN,
        ]);

        return $res[0]['id'];
    }

}
