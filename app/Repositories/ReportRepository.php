<?php

namespace App\Repositories;

use App\Models\Report;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class ReportRepository implements ReportRepositoryInterface
{
    private const WITH = [
        'reporter:id,username,display_name,avatar',
        'resolvedBy:id,username,display_name',
    ];

    public function create(array $data): Report
    {
        return Report::create($data);
    }

    public function find(int $id): ?Report
    {
        return Report::with(self::WITH)->with('reportable')->find($id);
    }

    public function hasOpenReportBy(int $reporterId, string $reportableType, int $reportableId): bool
    {
        return Report::query()
            ->where('reporter_id', $reporterId)
            ->where('reportable_type', $reportableType)
            ->where('reportable_id', $reportableId)
            ->pending()
            ->exists();
    }

    public function listForModeration(array $filters, int $page, int $perPage): LengthAwarePaginator
    {
        $query = Report::query()
            ->with(self::WITH);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['type'])) {
            $typeClass = match ($filters['type']) {
                'post' => \App\Models\Post::class,
                'comment' => \App\Models\Comment::class,
                default => null,
            };

            if ($typeClass) {
                $query->where('reportable_type', $typeClass);
            }
        }

        $this->applySort($query, $filters['sort'] ?? 'newest');

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    public function update(Report $report, array $data): Report
    {
        $report->update($data);

        return $report->fresh(self::WITH);
    }

    private function applySort(Builder $query, string $sort): void
    {
        match ($sort) {
            'oldest' => $query->orderBy('created_at'),
            default => $query->orderByDesc('created_at'),
        };
    }
}
