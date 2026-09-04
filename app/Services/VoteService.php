<?php

namespace App\Services;

use App\Models\Post;
use App\Models\PostVote;
use App\Models\User;
use App\Repositories\VoteRepositoryInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class VoteService
{
    public function __construct(
        private readonly VoteRepositoryInterface $voteRepository,
        private readonly HotScoreService $hotScoreService,
        private readonly FeedService $feedService,
        private readonly NotificationService $notificationService,
    ) {
    }

    /**
     * Upvote a post. Idempotent when the user already upvoted.
     *
     * @return array{post_id: int, user_vote: int, vote_score: int, upvote_count: int, downvote_count: int, hot_score: float}
     */
    public function upvote(User $user, Post $post): array
    {
        return $this->vote($user, $post, PostVote::UPVOTE);
    }

    /**
     * Downvote a post. Idempotent when the user already downvoted.
     *
     * @return array{post_id: int, user_vote: int, vote_score: int, upvote_count: int, downvote_count: int, hot_score: float}
     */
    public function downvote(User $user, Post $post): array
    {
        return $this->vote($user, $post, PostVote::DOWNVOTE);
    }

    /**
     * Place or switch a vote.
     */
    public function vote(User $user, Post $post, int $value): array
    {
        $this->guardVotable($post);

        return DB::transaction(function () use ($user, $post, $value) {
            $current = $this->voteRepository->getUserVote($user->id, $post->id);

            if ($current === $value) {
                return $this->stateOf($post);
            }

            if ($current === null) {
                $result = $this->voteRepository->insertVote($user->id, $post->id, $value);
            } else {
                $result = $this->voteRepository->updateVote($user->id, $post->id, $current, $value);
            }

            return $this->applyEffect($post, $user, $result['upvote_delta'], $result['downvote_delta'], $result['score_delta'], $value);
        });
    }

    /**
     * Remove the user's vote (becomes neutral). Idempotent.
     */
    public function removeVote(User $user, Post $post): array
    {
        $this->guardVotable($post);

        return DB::transaction(function () use ($user, $post) {
            $current = $this->voteRepository->getUserVote($user->id, $post->id);

            if ($current === null) {
                return $this->stateOf($post);
            }

            $result = $this->voteRepository->removeVote($user->id, $post->id, $current);

            return $this->applyEffect($post, $user, $result['upvote_delta'], $result['downvote_delta'], $result['score_delta'], null);
        });
    }

    /**
     * The user's current vote type on the post (1, -1, or null).
     */
    public function userVote(User $user, Post $post): ?int
    {
        return $this->voteRepository->getUserVote($user->id, $post->id);
    }

    /**
     * Apply aggregate changes to post counters, hot score, author karma, and cache.
     */
    private function applyEffect(Post $post, User $voter, int $upDelta, int $downDelta, int $scoreDelta, ?int $userVote): array
    {
        if ($scoreDelta !== 0 || $upDelta !== 0 || $downDelta !== 0) {
            $this->voteRepository->adjustPostCounts($post, $upDelta, $downDelta, $scoreDelta);
            $post->refresh();
        }

        if ($scoreDelta !== 0) {
            $this->hotScoreService->recalculate($post);
            $post->refresh();

            if ($post->user_id !== $voter->id) {
                $author = User::query()->where('id', $post->user_id)->lockForUpdate()->first();
                if ($author) {
                    $this->voteRepository->adjustUserKarma($author, $scoreDelta);
                }
            }

            $this->notificationService->notifyPostVoted($post, $voter->id);

            $this->feedService->invalidate();
            Cache::forget("post:related:{$post->id}");
            Cache::forget("post:navigation:{$post->id}");
        }

        return [
            'post_id' => $post->id,
            'user_vote' => $userVote,
            'vote_score' => $post->vote_score,
            'upvote_count' => $post->upvote_count,
            'downvote_count' => $post->downvote_count,
            'hot_score' => round((float) $post->hot_score, 2),
        ];
    }

    private function guardVotable(Post $post): void
    {
        if ($post->status !== 'published' || $post->is_locked || !$post->is_approved) {
            throw new UnprocessableEntityHttpException('This action cannot be performed on this post.');
        }
    }

    private function stateOf(Post $post): array
    {
        return [
            'post_id' => $post->id,
            'user_vote' => null,
            'vote_score' => $post->vote_score,
            'upvote_count' => $post->upvote_count,
            'downvote_count' => $post->downvote_count,
            'hot_score' => (float) $post->hot_score,
        ];
    }
}
