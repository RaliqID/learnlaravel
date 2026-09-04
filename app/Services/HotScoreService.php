<?php

namespace App\Services;

use App\Models\Post;

class HotScoreService
{
    public const COMMENT_WEIGHT = 3;

    public const LIKE_WEIGHT = 2;

    public const SHARE_WEIGHT = 5;

    public const AGE_DECAY_FACTOR = 1.8;

    /**
     * Hot Score = views + (comments * 3) + (likes * 2) + (shares * 5) - age decay.
     *
     * Note: share column does not exist in posts table yet (share tracking not
     * implemented). SHARE_WEIGHT is prepared so when the share feature is built,
     * only need to add share_count column and wire getShareCount() method to it
     * without changing the formula.
     */
    public function calculate(Post $post): float
    {
        $score = $post->view_count
            + ($post->comment_count * self::COMMENT_WEIGHT)
            + ($post->upvote_count * self::LIKE_WEIGHT)
            + ($this->getShareCount($post) * self::SHARE_WEIGHT)
            - $this->ageDecay($post);

        return max(0, round($score, 4));
    }

    /**
     * Calculate hot score for the given post and save to hot_score column.
     */
    public function recalculate(Post $post): float
    {
        $score = $this->calculate($post);

        $post->forceFill(['hot_score' => $score])->saveQuietly();

        return $score;
    }

    private function ageDecay(Post $post): float
    {
        $publishedAt = $post->published_at ?? $post->created_at;

        if (!$publishedAt) {
            return 0;
        }

        $ageInHours = max(0, (now()->getTimestamp() - $publishedAt->getTimestamp()) / 3600);

        return pow($ageInHours + 1, self::AGE_DECAY_FACTOR);
    }

    private function getShareCount(Post $post): int
    {
        return (int) ($post->share_count ?? 0);
    }
}
