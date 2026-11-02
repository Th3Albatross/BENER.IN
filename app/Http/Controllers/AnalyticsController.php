<?php

namespace App\Http\Controllers;

use App\Services\AnalyticsReportService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AnalyticsController
{
    /** Show the Staff-only Analytics dashboard for the selected report period. */
    public function index(Request $request, AnalyticsReportService $analyticsReportService)
    {
        $user = $request->user();
        abort_unless($user?->isStaff(), 403);

        // A missing period uses the default preset; all other values are validated.
        $request->merge(['period' => $request->query('period', '30d')]);
        $validated = $request->validate([
            'period' => ['required', 'string', Rule::in(['7d', '30d', '90d', 'all', 'custom'])],
            'start_date' => ['nullable', 'required_if:period,custom', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'required_if:period,custom', 'date_format:Y-m-d', 'after_or_equal:start_date'],
        ]);

        [$start, $end, $periodLabel] = $this->resolvePeriod($validated);
        $allTime = $validated['period'] === 'all';
        $dataset = $analyticsReportService->build($start, $end, $allTime);

        return view('analytics.index', [
            'filters' => [
                'period' => $validated['period'],
                'start_date' => $validated['period'] === 'custom' ? ($validated['start_date'] ?? '') : '',
                'end_date' => $validated['period'] === 'custom' ? ($validated['end_date'] ?? '') : '',
                'label' => $periodLabel,
            ],
            'summary' => $dataset['summary'],
            'insights' => $dataset['insights'],
            'range' => $dataset['period'],
            'charts' => [
                'trend' => $dataset['trend'],
                'statuses' => $dataset['statuses'],
                'severities' => $dataset['severities'],
                'categories' => $dataset['categories'],
                'facilities' => $dataset['facilities'],
            ],
        ]);
    }

    /**
     * @param  array{period: string, start_date?: string|null, end_date?: string|null}  $filters
     * @return array{0: ?CarbonImmutable, 1: ?CarbonImmutable, 2: string}
     */
    private function resolvePeriod(array $filters): array
    {
        $timezone = (string) config('app.timezone', 'UTC');
        $period = $filters['period'];

        if ($period === 'all') {
            return [null, null, 'Sepanjang waktu'];
        }

        if ($period === 'custom') {
            // The request validator guarantees these values exist and use YYYY-MM-DD.
            $start = CarbonImmutable::parse((string) $filters['start_date'], $timezone)->startOfDay();
            $end = CarbonImmutable::parse((string) $filters['end_date'], $timezone)->startOfDay();

            return [
                $start,
                $end,
                $start->format('d/m/Y').' s/d '.$end->format('d/m/Y'),
            ];
        }

        $days = match ($period) {
            '7d' => 7,
            '90d' => 90,
            default => 30,
        };

        $today = CarbonImmutable::now($timezone)->startOfDay();
        $start = $today->subDays($days - 1);
        $label = match ($period) {
            '7d' => '7 hari terakhir',
            '90d' => '90 hari terakhir',
            default => '30 hari terakhir',
        };

        return [$start, $today, $label];
    }
}
