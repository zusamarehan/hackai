<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class CommentAnalysisService
{
    protected string $openAiKey;

    public function __construct()
    {
        $this->openAiKey = config('services.openai.key');
    }

    public function hasAuthorReplied($replies, $author): int
    {
        $has = 0;

        foreach ($replies as $reply) {
            if ($reply['name'] == $author) {
                $has = 1;
            }
        }

        return $has;
    }

    public function analyze(array $comment, $postId, $author = null): array
    {
        $text = $comment['comment'] ?? '';
        $likes = $comment['likes'] ?? 0;
        $replies = $comment['replies_count'] ?? 0;
        $hasAuthor = $this->hasAuthorReplied($comment['replies'], $author);

        $analysis = $this->withICP($text);

        return [
            'post_id' => $postId,
            'sentiment' => $analysis['sentiment'],
            'language_style' => $analysis['language_style'],
            'engagement_score' => $this->getEngagementScore($likes, $replies),
            'icp_score' => $analysis['icp_score'],
            'icp_reasoning' => $analysis['icp_reasoning'],
            'comment' => $text,
            'likes' => $likes,
            'replies' => $replies,
            'has_author_replied' => $hasAuthor,
        ];

    }

    public function withICP(string $comment): array
    {
        $icpProfile = <<<EOT
You are an AI assistant that scores how well a given comment or reel transcript fits an Ideal Customer Profile (ICP) for a content creator who makes fun, humorous reels about developer concepts.

ICP Profile:
- Audience: Developers aged 18–35, mostly junior to mid-level, coding students, bootcamp grads, and hobbyists worldwide.
- Tone: Playful, excited, emoji-heavy, and casual, often using humor and relatable dev struggles.
- Themes: Developer frustrations, AI in tech, workplace humor, debugging struggles, product management jokes, imposter syndrome, and coding memes.
- Language: Conversational, sometimes sarcastic, with technical jargon like “story points,” “C-suite,” “estimates,” etc.

Comment/Reel Transcript:
"""
$comment
"""

Please analyze and return a JSON object with the following fields:

- sentiment: one of "positive", "neutral", or "negative"
- language_style: one of "excited", "playful", "emoji-heavy", "casual", or "internet-slang"
- icp_score: a float between 1 and 10 indicating how well this comment fits the ICP profile above
- icp_reasoning: a brief sentence explaining the ICP score based on tone, themes, and language

Return only valid JSON.
EOT;

        $response = Http::withToken($this->openAiKey)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => config('services.openai.model'),
                'messages' => [
                    ['role' => 'user', 'content' => $icpProfile],
                ],
                'temperature' => 0,
            ]);

        $content = $response['choices'][0]['message']['content'] ?? '{}';

        $result = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE
            || !isset($result['sentiment'], $result['language_style'], $result['icp_score'], $result['icp_reasoning'])) {
            return [
                'sentiment' => 'neutral',
                'language_style' => 'casual',
                'icp_score' => 0,
                'icp_reasoning' => 'Unable to parse AI response or incomplete data.',
            ];
        }

        return $result;
    }

    protected function getEngagementScore(int $likes, int $replyCount, int $maxPossible = 100): float
    {
        $rawScore = $likes + ($replyCount * 2);
        $normalized = ($rawScore / $maxPossible) * 10;

        return min(10, round($normalized, 1));
    }
}
