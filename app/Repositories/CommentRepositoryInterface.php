<?php

namespace App\Repositories;

use App\Models\Comment;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface CommentRepositoryInterface
{
    public function getPostComments(int $postId, string $sort, int $page, int $perPage): LengthAwarePaginator;

    public function getCommentWithReplies(int $commentId): ?Comment;

    public function getUserComments(int $userId, int $page, int $perPage): LengthAwarePaginator;

    public function create(array $data): Comment;

    public function update(Comment $comment, array $data): Comment;

    public function delete(Comment $comment): bool;

    public function restore(Comment $comment): bool;

    public function find(int $id): ?Comment;

    public function getUserCommentVote(int $userId, int $commentId): ?int;

    public function incrementCommentCount(int $postId): void;

    public function decrementCommentCount(int $postId): void;

    public function incrementRepliesCount(int $commentId): void;

    public function decrementRepliesCount(int $commentId): void;
}
