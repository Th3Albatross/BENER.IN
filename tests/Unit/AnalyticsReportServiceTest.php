<?php

namespace Tests\Unit;

use App\Models\Report;
use App\Services\AnalyticsReportService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsReportServiceTest extends TestCase
{
    use RefreshDatabase;

    private function date(string $value): CarbonImmutable
    {
        return CarbonImmutable::parse($value, config('app.timezone', 'UTC'));
    }

    private function report(array $attributes = []): Report
    {
        $timestamp = $attributes['created_at'] ?? $this->date('2026-01-10 12:00:00');

        return Report::factory()->create(array_merge([
            'status' => 'Pending',
            'severity' => 'Low',
            'categories_id' => null,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ], $attributes));
    }

    public function test_it_aggregates_total_status_severity_category_and_facility_counts(): void
    {
        $first = $this->report([
            'status' => 'Pending',
            'severity' => 'High',
            'created_at' => $this->date('2026-01-10 10:00:00'),
        ]);

        $this->report([
            'status' => 'Resolved',
            'severity' => 'Critical',
            'facilities_id' => $first->facilities_id,
            'categories_id' => null,
            'created_at' => $this->date('2026-01-11 10:00:00'),
        ]);

        $dataset = app(AnalyticsReportService::class)->build(
            $this->date('2026-01-10'),
            $this->date('2026-01-11')
        );

        $this->assertSame([
            'total' => 2,
            'pending' => 1,
            'resolved' => 1,
            'rejected' => 0,
        ], $dataset['summary']);

        $this->assertSame([
            ['label' => 'Pending', 'total' => 1],
            ['label' => 'Resolved', 'total' => 1],
            ['label' => 'Rejected', 'total' => 0],
        ], $dataset['statuses']);

        $this->assertSame([
            ['label' => 'Low', 'total' => 0],
            ['label' => 'Medium', 'total' => 0],
            ['label' => 'High', 'total' => 1],
            ['label' => 'Critical', 'total' => 1],
        ], $dataset['severities']);

        $this->assertSame([
            ['label' => 'Belum dikategorikan', 'total' => 2],
        ], $dataset['categories']);

        $this->assertSame(2, array_sum(array_column($dataset['facilities'], 'total')));
    }

    public function test_it_includes_both_date_boundaries_and_excludes_reports_outside_the_range(): void
    {
        $this->report(['created_at' => $this->date('2026-01-09 23:59:59')]);
        $this->report(['created_at' => $this->date('2026-01-10 00:00:00')]);
        $this->report(['created_at' => $this->date('2026-01-11 23:59:59')]);
        $this->report(['created_at' => $this->date('2026-01-12 00:00:00')]);

        $dataset = app(AnalyticsReportService::class)->build(
            $this->date('2026-01-10'),
            $this->date('2026-01-11')
        );

        $this->assertSame(2, $dataset['summary']['total']);
        $this->assertSame([
            ['label' => '2026-01-10', 'total' => 1],
            ['label' => '2026-01-11', 'total' => 1],
        ], $dataset['trend']);
    }

    public function test_it_fills_missing_daily_buckets_with_zero(): void
    {
        $this->report(['created_at' => $this->date('2026-01-10 12:00:00')]);
        $this->report(['created_at' => $this->date('2026-01-12 12:00:00')]);

        $dataset = app(AnalyticsReportService::class)->build(
            $this->date('2026-01-10'),
            $this->date('2026-01-12')
        );

        $this->assertSame([
            ['label' => '2026-01-10', 'total' => 1],
            ['label' => '2026-01-11', 'total' => 0],
            ['label' => '2026-01-12', 'total' => 1],
        ], $dataset['trend']);
    }

    public function test_it_returns_valid_empty_datasets_when_no_reports_match(): void
    {
        $dataset = app(AnalyticsReportService::class)->build(
            $this->date('2026-01-10'),
            $this->date('2026-01-12')
        );

        $this->assertSame([
            'total' => 0,
            'pending' => 0,
            'resolved' => 0,
            'rejected' => 0,
        ], $dataset['summary']);
        $this->assertSame([
            ['label' => '2026-01-10', 'total' => 0],
            ['label' => '2026-01-11', 'total' => 0],
            ['label' => '2026-01-12', 'total' => 0],
        ], $dataset['trend']);
        $this->assertSame([], $dataset['categories']);
        $this->assertSame([], $dataset['facilities']);
        $this->assertCount(3, $dataset['statuses']);
        $this->assertCount(4, $dataset['severities']);
    }

    public function test_all_time_uses_monthly_buckets_and_fills_missing_months(): void
    {
        $this->report(['created_at' => $this->date('2026-01-20 12:00:00')]);
        $this->report(['created_at' => $this->date('2026-03-02 12:00:00')]);

        $dataset = app(AnalyticsReportService::class)->build(null, null, true);

        $this->assertSame('month', $dataset['period']['granularity']);
        $this->assertSame([
            ['label' => '2026-01', 'total' => 1],
            ['label' => '2026-02', 'total' => 0],
            ['label' => '2026-03', 'total' => 1],
        ], $dataset['trend']);
    }

    public function test_it_counts_urgent_open_reports_and_resolution_rate(): void
    {
        $at = $this->date('2026-01-10 08:00:00');

        $this->report(['status' => 'Pending', 'severity' => 'Critical', 'created_at' => $at]);
        $this->report(['status' => 'Pending', 'severity' => 'High', 'created_at' => $at]);
        $this->report(['status' => 'Pending', 'severity' => 'Low', 'created_at' => $at]);
        $this->report(['status' => 'Resolved', 'severity' => 'Critical', 'created_at' => $at]);

        $insights = app(AnalyticsReportService::class)
            ->build($this->date('2026-01-10'), $this->date('2026-01-11'))['insights'];

        // Only Pending reports with High/Critical severity need attention.
        $this->assertSame(2, $insights['urgent_open']);
        $this->assertSame(25.0, $insights['resolution_rate']);
        // 4 reports spread over a 2-day range.
        $this->assertSame(2.0, $insights['daily_average']);
    }

    public function test_it_compares_with_the_previous_period_of_the_same_length(): void
    {
        // Selected range: Jan 10-11 (2 days). Previous range: Jan 8-9.
        $this->report(['created_at' => $this->date('2026-01-09 12:00:00')]);
        $this->report(['created_at' => $this->date('2026-01-10 12:00:00')]);
        $this->report(['created_at' => $this->date('2026-01-10 13:00:00')]);
        $this->report(['created_at' => $this->date('2026-01-11 12:00:00')]);
        // Outside both ranges, must not be counted.
        $this->report(['created_at' => $this->date('2026-01-07 12:00:00')]);

        $insights = app(AnalyticsReportService::class)
            ->build($this->date('2026-01-10'), $this->date('2026-01-11'))['insights'];

        $this->assertSame(1, $insights['previous_total']);
        $this->assertSame(200.0, $insights['change_percent']);
    }

    public function test_change_percent_is_null_when_the_previous_period_is_empty(): void
    {
        $this->report(['created_at' => $this->date('2026-01-10 12:00:00')]);

        $insights = app(AnalyticsReportService::class)
            ->build($this->date('2026-01-10'), $this->date('2026-01-11'))['insights'];

        $this->assertSame(0, $insights['previous_total']);
        $this->assertNull($insights['change_percent']);
    }

    public function test_all_time_has_no_previous_period_but_still_has_a_daily_average(): void
    {
        $this->report(['created_at' => $this->date('2026-01-10 12:00:00')]);
        $this->report(['created_at' => $this->date('2026-01-12 12:00:00')]);

        $insights = app(AnalyticsReportService::class)->build(null, null, true)['insights'];

        $this->assertNull($insights['previous_total']);
        $this->assertNull($insights['change_percent']);
        // 2 reports over Jan 10-12 (3 days) = 0.67, rounded to one decimal.
        $this->assertSame(0.7, $insights['daily_average']);
    }

    public function test_insights_are_empty_but_valid_when_there_are_no_reports(): void
    {
        $insights = app(AnalyticsReportService::class)
            ->build($this->date('2026-01-10'), $this->date('2026-01-11'))['insights'];

        $this->assertNull($insights['resolution_rate']);
        $this->assertSame(0, $insights['urgent_open']);
        $this->assertSame(0.0, $insights['daily_average']);
    }
}
