<?php

namespace App\Repositories;

use App\Models\Post;
use App\Models\PostVote;
use App\Models\User;

interface VoteRepositoryInterface
{
    public function getUserVote(int $userId, int $postId): ?int;

    /**
     * Create new vote (only when none exists). Returns the vote + deltas.
     *
     * @return array{vote: PostVote, upvote_delta: int, downvote_delta: int, score_delta: int}
     */
    public function insertVote(int $userId, int $postId, int $value): array;

    /**
     * Update existing vote. Returns deltas.
     *
     * @return array{vote: ?PostVote, upvote_delta: int, downvote_delta: int, score_delta: int}
     */
    public function updateVote(int $userId, int $postId, int $currentValue, int $newValue): array;

    /**
     * Remove existing vote. Returns deltas.
     *
     * @return array{upvote_delta: int, downvote_delta: int, score_delta: int}
     */
    public function removeVote(int $userId, int $postId, int $currentValue): array;

    // Generic methods for comment votes

    public function getUserCommentVote(int $userId, int $commentId): ?int;

    public function insertCommentVote(int $userId, int $commentId, int $value): array;

    public function updateCommentVote(int $userId, int $commentId, int $currentValue, int $newValue): array;

    public function removeCommentVote(int $userId, int $commentId): array;

    public function countCommentVotes(int $commentId): array;

    /**
     * Apply aggregated counts to the post row (atomic).
     */
    public function adjustPostCounts(Post $post, int $upvoteDelta, int $downvoteDelta, int $scoreDelta): void;

    public function adjustUserKarma(User $user, int $delta): void;

    public function countVoteTotals(Post $post): array;

    public function hasVotes(Post $post): bool;
}
