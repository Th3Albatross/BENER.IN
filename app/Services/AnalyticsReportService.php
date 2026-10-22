<?php

namespace App\Services;

use App\Models\Report;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;

/**
 * Builds the shared, read-only dataset used by the Analytics page and PDF.
 * This service reads existing tables only; it does not persist analytics data.
 */
class AnalyticsReportService
{
    private const STATUSES = ['Pending', 'Resolved', 'Rejected'];

    private const SEVERITIES = ['Low', 'Medium', 'High', 'Critical'];

    /**
     * @return array{
     *   period: array{all_time: bool, granularity: string, start_date: ?string, end_date: ?string},
     *   summary: array{total: int, pending: int, resolved: int, rejected: int},
     *   insights: array{resolution_rate: ?float, urgent_open: int, daily_average: ?float, previous_total: ?int, change_percent: ?float},
     *   trend: array<int, array{label: string, total: int}>,
     *   categories: array<int, array{label: string, total: int}>,
     *   facilities: array<int, array{label: string, total: int}>,
     *   severities: array<int, array{label: string, total: int}>,
     *   statuses: array<int, array{label: string, total: int}>
     * }
     */
    public function build(
        ?CarbonImmutable $start,
        ?CarbonImmutable $end,
        bool $allTime = false
    ): array {
        if ($allTime) {
            // In all-time mode the caller must not provide conflicting date bounds.
            $start = null;
            $end = null;
        } else {
            if ($start === null || $end === null) {
                throw new InvalidArgumentException('Tanggal awal dan akhir wajib diisi kecuali untuk periode sepanjang waktu.');
            }

            $timezone = (string) config('app.timezone', 'UTC');
            $start = $start->setTimezone($timezone)->startOfDay();
            $end = $end->setTimezone($timezone)->endOfDay();

            if ($start->greaterThan($end)) {
                throw new InvalidArgumentException('Tanggal awal tidak boleh melewati tanggal akhir.');
            }
        }

        $baseQuery = Report::query();

        if (!$allTime) {
            $baseQuery->where('reports.created_at', '>=', $start)
                ->where('reports.created_at', '<=', $end);
        }

        $total = (int) (clone $baseQuery)->count('reports.id');
        $statusCounts = (clone $baseQuery)
            ->selectRaw('reports.status as label, COUNT(reports.id) as total')
            ->groupBy('reports.status')
            ->pluck('total', 'label');

        $severityCounts = (clone $baseQuery)
            ->selectRaw('reports.severity as label, COUNT(reports.id) as total')
            ->groupBy('reports.severity')
            ->pluck('total', 'label');

        $categories = (clone $baseQuery)
            ->leftJoin('categories', 'reports.categories_id', '=', 'categories.id')
            ->selectRaw('COALESCE(categories.name, ?) as label, COUNT(reports.id) as total', ['Belum dikategorikan'])
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('total')
            ->orderBy('label')
            ->get()
            ->map(fn ($row): array => [
                'label' => (string) $row->label,
                'total' => (int) $row->total,
            ])
            ->values()
            ->all();

        $facilities = (clone $baseQuery)
            ->join('facilities', 'reports.facilities_id', '=', 'facilities.id')
            ->selectRaw('facilities.name as label, COUNT(reports.id) as total')
            ->groupBy('facilities.id', 'facilities.name')
            ->orderByDesc('total')
            ->orderBy('label')
            ->get()
            ->map(fn ($row): array => [
                'label' => (string) $row->label,
                'total' => (int) $row->total,
            ])
            ->values()
            ->all();

        $periodRange = $this->periodRange($start, $end, $allTime);
        $granularity = $this->trendGranularity(
            $periodRange['trend_start'],
            $periodRange['trend_end'],
            $allTime
        );

        $trend = $this->buildTrend(
            clone $baseQuery,
            $periodRange['trend_start'],
            $periodRange['trend_end'],
            $granularity
        );

        return [
            'period' => [
                'all_time' => $allTime,
                'granularity' => $granularity,
                'start_date' => $periodRange['display_start']?->toDateString(),
                'end_date' => $periodRange['display_end']?->toDateString(),
            ],
            'summary' => [
                'total' => $total,
                'pending' => (int) $statusCounts->get('Pending', 0),
                'resolved' => (int) $statusCounts->get('Resolved', 0),
                'rejected' => (int) $statusCounts->get('Rejected', 0),
            ],
            'insights' => $this->buildInsights(
                $baseQuery,
                $total,
                (int) $statusCounts->get('Resolved', 0),
                $start,
                $end,
                $periodRange
            ),
            'trend' => $trend,
            'categories' => $categories,
            'facilities' => $facilities,
            'severities' => $this->fixedCountRows(self::SEVERITIES, $severityCounts),
            'statuses' => $this->fixedCountRows(self::STATUSES, $statusCounts),
        ];
    }

    /**
     * Derived, read-only indicators shown next to the raw counts. They are kept
     * outside "summary" so the base counts keep a stable, minimal shape.
     *
     * @param array{display_start: ?CarbonImmutable, display_end: ?CarbonImmutable, trend_start: ?CarbonImmutable, trend_end: ?CarbonImmutable} $periodRange
     * @return array{resolution_rate: ?float, urgent_open: int, daily_average: ?float, previous_total: ?int, change_percent: ?float}
     */
    private function buildInsights(
        Builder $baseQuery,
        int $total,
        int $resolved,
        ?CarbonImmutable $start,
        ?CarbonImmutable $end,
        array $periodRange
    ): array {
        // Reports that are still open and serious enough to need quick action.
        $urgentOpen = (int) (clone $baseQuery)
            ->where('reports.status', 'Pending')
            ->whereIn('reports.severity', ['High', 'Critical'])
            ->count('reports.id');

        $displayStart = $periodRange['display_start'];
        $displayEnd = $periodRange['display_end'];
        $days = $displayStart !== null && $displayEnd !== null
            ? (int) round($displayStart->startOfDay()->diffInDays($displayEnd->startOfDay())) + 1
            : null;

        $previousTotal = null;
        $changePercent = null;

        // The previous period is the same number of days directly before the
        // selected one. It is not defined for the all-time view.
        if ($start !== null && $end !== null && $days !== null) {
            $previousTotal = (int) Report::query()
                ->where('reports.created_at', '>=', $start->subDays($days)->startOfDay())
                ->where('reports.created_at', '<=', $start->subDay()->endOfDay())
                ->count('reports.id');

            if ($previousTotal > 0) {
                $changePercent = round((($total - $previousTotal) / $previousTotal) * 100, 1);
            }
        }

        return [
            'resolution_rate' => $total > 0 ? round(($resolved / $total) * 100, 1) : null,
            'urgent_open' => $urgentOpen,
            'daily_average' => $days !== null && $days > 0 ? round($total / $days, 1) : null,
            'previous_total' => $previousTotal,
            'change_percent' => $changePercent,
        ];
    }

    /** @return array{display_start: ?CarbonImmutable, display_end: ?CarbonImmutable, trend_start: ?CarbonImmutable, trend_end: ?CarbonImmutable} */
    private function periodRange(?CarbonImmutable $start, ?CarbonImmutable $end, bool $allTime): array
    {
        if (!$allTime) {
            return [
                'display_start' => $start,
                'display_end' => $end,
                'trend_start' => $start,
                'trend_end' => $end,
            ];
        }

        $firstCreatedAt = (clone Report::query())->min('reports.created_at');
        $lastCreatedAt = (clone Report::query())->max('reports.created_at');

        if (!$firstCreatedAt || !$lastCreatedAt) {
            return [
                'display_start' => null,
                'display_end' => null,
                'trend_start' => null,
                'trend_end' => null,
            ];
        }

        $timezone = (string) config('app.timezone', 'UTC');
        $first = CarbonImmutable::parse((string) $firstCreatedAt, $timezone);
        $last = CarbonImmutable::parse((string) $lastCreatedAt, $timezone);

        return [
            'display_start' => $first,
            'display_end' => $last,
            'trend_start' => $first,
            'trend_end' => $last,
        ];
    }

    private function trendGranularity(?CarbonImmutable $start, ?CarbonImmutable $end, bool $allTime): string
    {
        if ($allTime || $start === null || $end === null) {
            return 'month';
        }

        return $start->startOfDay()->diffInDays($end->startOfDay()) < 90 ? 'day' : 'month';
    }

    /** @return array<int, array{label: string, total: int}> */
    private function buildTrend(
        Builder $query,
        ?CarbonImmutable $start,
        ?CarbonImmutable $end,
        string $granularity
    ): array {
        if ($start === null || $end === null) {
            return [];
        }

        $bucketExpression = $this->bucketExpression($granularity);
        $totalsByBucket = $query
            ->selectRaw($bucketExpression . ' as bucket, COUNT(reports.id) as total')
            ->groupByRaw($bucketExpression)
            ->orderBy('bucket')
            ->get()
            ->mapWithKeys(fn ($row): array => [(string) $row->bucket => (int) $row->total]);

        $cursor = $granularity === 'month' ? $start->startOfMonth() : $start->startOfDay();
        $lastBucket = $granularity === 'month' ? $end->startOfMonth() : $end->startOfDay();
        $trend = [];

        while ($cursor->lessThanOrEqualTo($lastBucket)) {
            $label = $granularity === 'month' ? $cursor->format('Y-m') : $cursor->format('Y-m-d');
            $trend[] = [
                'label' => $label,
                'total' => (int) $totalsByBucket->get($label, 0),
            ];
            $cursor = $granularity === 'month' ? $cursor->addMonth() : $cursor->addDay();
        }

        return $trend;
    }

    /** @param Collection<string, int|string> $counts @return array<int, array{label: string, total: int}> */
    private function fixedCountRows(array $labels, Collection $counts): array
    {
        return array_map(
            fn (string $label): array => [
                'label' => $label,
                'total' => (int) $counts->get($label, 0),
            ],
            $labels
        );
    }

    /**
     * Return an SQL date-bucket expression for the active database driver.
     * The expression is selected from constants, never from request input.
     */
    private function bucketExpression(string $granularity): string
    {
        $driver = DB::connection()->getDriverName();

        if ($granularity === 'month') {
            return match ($driver) {
                'mysql', 'mariadb' => "DATE_FORMAT(reports.created_at, '%Y-%m')",
                'sqlite' => "strftime('%Y-%m', reports.created_at)",
                'pgsql' => "TO_CHAR(DATE_TRUNC('month', reports.created_at), 'YYYY-MM')",
                'sqlsrv' => "CONVERT(char(7), reports.created_at, 126)",
                default => throw new LogicException('Database driver belum didukung untuk tren Analytics: ' . $driver),
            };
        }

        return match ($driver) {
            'mysql', 'mariadb', 'sqlite' => 'DATE(reports.created_at)',
            'pgsql' => 'CAST(reports.created_at AS DATE)',
            'sqlsrv' => 'CAST(reports.created_at AS date)',
            default => throw new LogicException('Database driver belum didukung untuk tren Analytics: ' . $driver),
        };
    }
}
