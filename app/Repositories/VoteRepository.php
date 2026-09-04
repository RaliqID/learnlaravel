<?php

namespace App\Repositories;

use App\Models\Post;
use App\Models\PostVote;
use App\Models\User;

class VoteRepository implements VoteRepositoryInterface
{
    public function getUserVote(int $userId, int $postId): ?int
    {
        return PostVote::query()
            ->where('user_id', $userId)
            ->where('post_id', $postId)
            ->value('vote_type');
    }

    public function insertVote(int $userId, int $postId, int $value): array
    {
        try {
            $vote = PostVote::create([
                'user_id' => $userId,
                'post_id' => $postId,
                'vote_type' => $value,
            ]);

            return [
                'vote' => $vote,
                'upvote_delta' => $value === 1 ? 1 : 0,
                'downvote_delta' => $value === -1 ? 1 : 0,
                'score_delta' => $value,
            ];
        } catch (\Illuminate\Database\UniqueConstraintViolationException) {
            return [
                'vote' => PostVote::where('user_id', $userId)->where('post_id', $postId)->first(),
                'upvote_delta' => 0,
                'downvote_delta' => 0,
                'score_delta' => 0,
            ];
        }
    }

    public function updateVote(int $userId, int $postId, int $currentValue, int $newValue): array
    {
        $vote = PostVote::where('user_id', $userId)
            ->where('post_id', $postId)
            ->lockForUpdate()
            ->first();

        if (!$vote) {
            return [
                'vote' => null,
                'upvote_delta' => 0,
                'downvote_delta' => 0,
                'score_delta' => 0,
            ];
        }

        if ($currentValue === $newValue) {
            return [
                'vote' => $vote,
                'upvote_delta' => 0,
                'downvote_delta' => 0,
                'score_delta' => 0,
            ];
        }

        $vote->update(['vote_type' => $newValue]);

        return [
            'vote' => $vote,
            'upvote_delta' => ($newValue === 1 ? 1 : 0) - ($currentValue === 1 ? 1 : 0),
            'downvote_delta' => ($newValue === -1 ? 1 : 0) - ($currentValue === -1 ? 1 : 0),
            'score_delta' => $newValue - $currentValue,
        ];
    }

    public function removeVote(int $userId, int $postId, int $currentValue): array
    {
        $vote = PostVote::where('user_id', $userId)->where('post_id', $postId);

        if ($currentValue === null) {
            return ['upvote_delta' => 0, 'downvote_delta' => 0, 'score_delta' => 0];
        }

        $vote->delete();

        return [
            'upvote_delta' => $currentValue === 1 ? -1 : 0,
            'downvote_delta' => $currentValue === -1 ? -1 : 0,
            'score_delta' => -$currentValue,
        ];
    }

    public function adjustPostCounts(Post $post, int $upvoteDelta, int $downvoteDelta, int $scoreDelta): void
    {
        $post->forceFill([
            'upvote_count' => max(0, $post->upvote_count + $upvoteDelta),
            'downvote_count' => max(0, $post->downvote_count + $downvoteDelta),
            'vote_score' => $post->vote_score + $scoreDelta,
        ])->save();
    }

    public function adjustUserKarma(User $user, int $delta): void
    {
        if ($delta !== 0) {
            $user->forceFill(['karma_score' => $user->karma_score + $delta])->save();
        }
    }

    public function countVoteTotals(Post $post): array
    {
        return [
            'upvotes' => $post->votes()->where('vote_type', 1)->count(),
            'downvotes' => $post->votes()->where('vote_type', -1)->count(),
        ];
    }

    public function hasVotes(Post $post): bool
    {
        return $post->votes()->exists();
    }

    public function getUserCommentVote(int $userId, int $commentId): ?int
    {
        return \App\Models\CommentVote::query()
            ->where('user_id', $userId)
            ->where('comment_id', $commentId)
            ->value('vote_type');
    }

    public function insertCommentVote(int $userId, int $commentId, int $value): array
    {
        try {
            $vote = \App\Models\CommentVote::create([
                'user_id' => $userId,
                'comment_id' => $commentId,
                'vote_type' => $value,
            ]);

            return [
                'vote' => $vote,
                'upvote_delta' => $value === 1 ? 1 : 0,
                'downvote_delta' => $value === -1 ? 1 : 0,
                'score_delta' => $value,
            ];
        } catch (\Illuminate\Database\UniqueConstraintViolationException) {
            return [
                'vote' => \App\Models\CommentVote::where('user_id', $userId)->where('comment_id', $commentId)->first(),
                'upvote_delta' => 0,
                'downvote_delta' => 0,
                'score_delta' => 0,
            ];
        }
    }

    public function updateCommentVote(int $userId, int $commentId, int $currentValue, int $newValue): array
    {
        $vote = \App\Models\CommentVote::where('user_id', $userId)->where('comment_id', $commentId)->lockForUpdate()->first();

        if (!$vote) {
            return ['vote' => null, 'upvote_delta' => 0, 'downvote_delta' => 0, 'score_delta' => 0];
        }

        if ($currentValue === $newValue) {
            return ['vote' => $vote, 'upvote_delta' => 0, 'downvote_delta' => 0, 'score_delta' => 0];
        }

        $vote->update(['vote_type' => $newValue]);

        return [
            'vote' => $vote,
            'upvote_delta' => ($newValue === 1 ? 1 : 0) - ($currentValue === 1 ? 1 : 0),
            'downvote_delta' => ($newValue === -1 ? 1 : 0) - ($currentValue === -1 ? 1 : 0),
            'score_delta' => $newValue - $currentValue,
        ];
    }

    public function removeCommentVote(int $userId, int $commentId): array
    {
        $vote = \App\Models\CommentVote::where('user_id', $userId)->where('comment_id', $commentId);

        $existing = $vote->value('vote_type');

        if ($existing === null) {
            return ['upvote_delta' => 0, 'downvote_delta' => 0, 'score_delta' => 0];
        }

        $vote->delete();

        return [
            'upvote_delta' => $existing === 1 ? -1 : 0,
            'downvote_delta' => $existing === -1 ? -1 : 0,
            'score_delta' => -$existing,
        ];
    }

    public function countCommentVotes(int $commentId): array
    {
        return [
            'upvotes' => \App\Models\CommentVote::where('comment_id', $commentId)->where('vote_type', 1)->count(),
            'downvotes' => \App\Models\CommentVote::where('comment_id', $commentId)->where('vote_type', -1)->count(),
        ];
    }
}
