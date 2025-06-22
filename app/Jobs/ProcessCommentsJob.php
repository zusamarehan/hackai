<?php

namespace App\Jobs;

use App\Services\CommentAnalysisService;
use App\Services\SupabaseService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class ProcessCommentsJob implements ShouldQueue
{
    use Queueable;
    public $comments;
    protected $supabase;
    public CommentAnalysisService $analyzer;
    public $postId = null;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        $this->comments = json_decode(Storage::disk('public')->get('reels.json'), true);
        $this->analyzer = new CommentAnalysisService();
        $this->supabase = new SupabaseService;
    }

    public function initiateAnalysis(): void
    {
        $res = $this->supabase->insert('posts', [
            'url' => 'https://www.instagram.com/p/DJ90RZlNas-/'
        ]);

        $this->postId = $res[0]['id'];
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->initiateAnalysis();

        $overall = [];

        $chunks = array_chunk($this->comments, 2); // Process 5 at a time

        foreach ($chunks as $comment) {
            $data = [];
            foreach ($comment as $single) {
                $data[] = $this->analyzer->analyze($single, $this->postId);
            }

            array_push($overall, ... $data);
            $this->supabase->insertBatch('comments', $data);
        }

        $summary = $this->summarizeAnalysis($overall);

        $this->supabase->update('posts', $summary, ['id' => "eq.{$this->postId}"]);
    }

    private function summarizeAnalysis($data): array
    {
        $totalEngagement = 0;
        $totalICP = 0;

        $sentimentCounts = [
            'positive' => 0,
            'neutral' => 0,
            'negative' => 0,
        ];

        $styleCounts = [
            'excited' => 0,
            'playful' => 0,
            'emoji-heavy' => 0,
            'casual' => 0,
            'internet-slang' => 0,
        ];

        $allReasons = [];

        foreach ($data as $comment) {

            $totalEngagement += $comment['engagement_score'] ?? 0;
            $totalICP += $comment['icp_score'] ?? 0;

            $sentiment = $comment['sentiment'] ?? 'neutral';
            if (isset($sentimentCounts[$sentiment])) {
                $sentimentCounts[$sentiment]++;
            }

            $style = $analyzed['language_style'] ?? 'casual';
            if (isset($styleCounts[$style])) {
                $styleCounts[$style]++;
            }

            $allReasons[] = $comment['icp_reasoning'];
        }

        $count = count($this->comments);
        $averageEngagement = $count > 0 ? round($totalEngagement / $count, 2) : 0;
        $averageICP = $count > 0 ? round($totalICP / $count, 2) : 0;

        arsort($styleCounts);

        $overallICP = $this->summarizeIcpReasoning($allReasons);

        return [
            'engagement' => $averageEngagement,
            'icp' => $averageICP,
            'icp_reasoning' => $overallICP,
            'sentiment_distribution' => $sentimentCounts,
            'language_style_distribution' => $styleCounts
        ];
    }

    public function summarizeIcpReasoning($reasons): string
    {
        $reasoningsText = implode("\n- ", $reasons);

        $prompt = <<<PROMPT
            You are summarizing multiple short ICP reasoning snippets from individual comments.

            Task:
            - Write a concise summary (2–3 sentences max) that reflects how well the audience overall aligns with the Ideal Customer Profile (ICP).
            - Focus on tone, themes, and language patterns across the comments.
            - Highlight both strengths and gaps in alignment.


            Input:
                ICP Reasonings:
                - $reasoningsText
            PROMPT;

        $response = Http::withToken(config('services.openai.key'))
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => config('services.openai.model'),
                'messages' => [
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => 0.3,
            ]);

        return trim($response['choices'][0]['message']['content'] ?? 'Summary not available.');
    }

}
