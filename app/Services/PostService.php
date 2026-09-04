<?php

namespace App\Services;

use App\Models\Post;
use App\Repositories\PostRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PostService
{
    public function __construct(
        private readonly PostRepositoryInterface $repository,
        private readonly HotScoreService $hotScoreService,
        private readonly ImageService $imageService,
        private readonly FeedService $feedService,
        private readonly TopicService $topicService,
    ) {
    }

    public function store(array $data, ?UploadedFile $image = null): Post
    {
        return DB::transaction(function () use ($data, $image) {
            $data['slug'] = $this->generateSlug($data['title']);
            $data['content_html'] = $this->renderMarkdown($data['content'] ?? null);
            $data['meta_title'] = $data['meta_title'] ?? Str::limit($data['title'], 60);
            $data['meta_description'] = $data['meta_description'] ?? Str::limit(strip_tags($data['content'] ?? ''), 160);
            $data['status'] = $data['status'] ?? 'draft';

            if ($image) {
                $uploaded = $this->imageService->upload($image);
                $data['image_url'] = $uploaded['image_url'];
                $data['thumbnail_url'] = $uploaded['thumbnail_url'];
                $data['post_type'] = 'image';
            }

            if (($data['status'] ?? 'draft') === 'published') {
                $data['published_at'] = now();
                $data['last_activity_at'] = now();
            }

            $post = $this->repository->create($data);

            if ($post->status === 'published') {
                $this->hotScoreService->recalculate($post);
                $this->onPublished($post);
            }

            return $post->fresh(['user', 'topic']);
        });
    }

    public function update(Post $post, array $data, ?UploadedFile $image = null): Post
    {
        return DB::transaction(function () use ($post, $data, $image) {
            $wasPublished = $post->status === 'published';

            if (isset($data['title']) && $data['title'] !== $post->title) {
                $data['slug'] = $this->generateSlug($data['title'], $post->id);
            }

            if (array_key_exists('content', $data)) {
                $data['content_html'] = $this->renderMarkdown($data['content']);
            }

            if ($image) {
                if ($post->image_url && $post->thumbnail_url) {
                    $this->imageService->deleteByUrls($post->image_url, $post->thumbnail_url);
                }
                $uploaded = $this->imageService->upload($image);
                $data['image_url'] = $uploaded['image_url'];
                $data['thumbnail_url'] = $uploaded['thumbnail_url'];
                $data['post_type'] = 'image';
            }

            if (!$wasPublished && ($data['status'] ?? $post->status) === 'published') {
                $data['published_at'] = now();
                $data['last_activity_at'] = now();
            }

            if ($wasPublished) {
                $data['edited_at'] = now();
            }

            $updated = $this->repository->update($post, $data);

            if (!$wasPublished && $updated->status === 'published') {
                $this->onPublished($updated);
            }

            if ($updated->status === 'published') {
                $this->hotScoreService->recalculate($updated);
            }

            $this->invalidateCaches($updated);

            return $updated;
        });
    }

    public function destroy(Post $post): bool
    {
        return DB::transaction(function () use ($post) {
            $deleted = $this->repository->delete($post);

            if ($deleted) {
                $this->onRemoved($post);
            }

            return $deleted;
        });
    }

    public function restore(Post $post): bool
    {
        return DB::transaction(function () use ($post) {
            $restored = $this->repository->restore($post);

            if ($restored && $post->status === 'published') {
                $this->onPublished($post);
            }

            return $restored;
        });
    }

    public function findDuplicateUrl(string $url, ?int $excludePostId = null): ?Post
    {
        return $this->repository->findDuplicateUrl($url, $excludePostId);
    }

    public function getUserPosts(int $userId, string $status = 'published', int $page = 1, int $perPage = 20): LengthAwarePaginator
    {
        return $this->repository->getUserPosts($userId, $status, $page, $perPage);
    }

    public function resolveForReading(string $identifier): ?Post
    {
        return is_numeric($identifier)
            ? $this->repository->findById((int) $identifier)
            : $this->repository->findBySlug($identifier);
    }

    public function recordView(Post $post): void
    {
        $this->repository->incrementViewCount($post);
    }

    public function relatedPosts(Post $post): Collection
    {
        // No object caching: Eloquent collections fail unserialize on the
        // database cache store (__PHP_Incomplete_Class). Query directly.
        return $this->repository->relatedPosts($post);
    }

    /**
     * @return array{previous: ?Post, next: ?Post}
     */
    public function navigation(Post $post): array
    {
        if ($post->published_at === null || $post->status !== 'published') {
            return ['previous' => null, 'next' => null];
        }

        return [
            'previous' => $this->repository->previousPost($post),
            'next' => $this->repository->nextPost($post),
        ];
    }

    private function generateSlug(string $title, ?int $excludeId = null): string
    {
        $base = Str::slug($title);
        $slug = $base;
        $counter = 1;

        while ($this->repository->isSlugTaken($slug, $excludeId)) {
            $slug = "{$base}-{$counter}";
            $counter++;
        }

        return $slug;
    }

    private function renderMarkdown(?string $content): ?string
    {
        if ($content === null || $content === '') {
            return null;
        }

        return Str::markdown($content, ['html_input' => 'strip', 'allow_unsafe_links' => false]);
    }

    private function onPublished(Post $post): void
    {
        $post->topic()->increment('post_count');
        $this->invalidateCaches($post);
    }

    private function onRemoved(Post $post): void
    {
        if ($post->status === 'published') {
            $post->topic()
                ->where('post_count', '>', 0)
                ->decrement('post_count');
        }

        $this->invalidateCaches($post);
    }

    private function invalidateCaches(Post $post): void
    {
        $this->feedService->invalidate();
        $this->topicService->invalidate();

        Cache::forget("post:related:{$post->id}");
        Cache::forget("post:navigation:{$post->id}");
    }
}
