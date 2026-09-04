<?php

namespace App\Repositories;

use App\Models\Report;
use Illuminate\Pagination\LengthAwarePaginator;

interface ReportRepositoryInterface
{
    public function create(array $data): Report;

    public function find(int $id): ?Report;

    public function hasOpenReportBy(int $reporterId, string $reportableType, int $reportableId): bool;

    /**
     * @param  array<string, mixed>  $filters
     */
    public function listForModeration(array $filters, int $page, int $perPage): LengthAwarePaginator;

    public function update(Report $report, array $data): Report;
}
