<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Facility;
use App\Models\Report;
use App\Models\Response;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\FacilitySeeder;
use Database\Seeders\ReportSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_it_creates_one_account_for_each_role(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(1, User::where('role', User::ADMIN_ROLE)->count());
        $this->assertSame(1, User::where('role', 'petugas')->count());
        $this->assertGreaterThan(0, User::where('role', 'siswa')->count());
    }

    public function test_the_seeded_accounts_can_log_in(): void
    {
        $this->seed(UserSeeder::class);

        $this->post(route('login'), [
            'login' => 'admin@benerin.test',
            'password' => 'password',
        ])->assertRedirect();

        $this->assertAuthenticated();
    }

    public function test_seeded_students_get_a_nis_and_classroom(): void
    {
        $this->seed(UserSeeder::class);

        foreach (User::where('role', 'siswa')->get() as $siswa) {
            $this->assertNotNull($siswa->nis, "Student {$siswa->id} has no NIS.");
            $this->assertMatchesRegularExpression('/^\d{4,20}$/', $siswa->nis);
            $this->assertNotNull($siswa->classroom, "Student {$siswa->id} has no classroom.");
        }

        // Staff keep email-only identities.
        foreach (User::whereIn('role', [User::ADMIN_ROLE, 'petugas'])->get() as $staff) {
            $this->assertNull($staff->nis);
            $this->assertNull($staff->classroom);
        }
    }

    public function test_a_seeded_student_can_log_in_with_their_nis(): void
    {
        $this->seed(UserSeeder::class);

        $this->post(route('login'), [
            'login' => '1000000001',
            'password' => 'password',
        ])->assertRedirect();

        $this->assertAuthenticated();
    }

    public function test_it_seeds_categories_and_facilities(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(count(CategorySeeder::CATEGORIES), Category::count());
        $this->assertSame(count(FacilitySeeder::FACILITIES), Facility::count());

        // Assert against the seeder's own lists rather than hard-coded names,
        // so renaming the master data does not break this test.
        $this->assertDatabaseHas('categories', ['name' => CategorySeeder::CATEGORIES[0]]);
        $this->assertDatabaseHas('facilities', ['name' => FacilitySeeder::FACILITIES[0]]);
    }

    public function test_it_seeds_reports_that_reference_real_master_data(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertGreaterThan(0, Report::count());

        // Every report must point at an existing category, facility and reporter.
        foreach (Report::all() as $report) {
            $this->assertNotNull($report->facility, "Report {$report->id} has no facility.");
            $this->assertNotNull($report->user, "Report {$report->id} has no reporter.");
            $this->assertContains($report->status, ['Pending', 'Resolved', 'Rejected']);
            $this->assertContains($report->severity, ['Low', 'Medium', 'High', 'Critical']);
        }
    }

    public function test_it_seeds_discussions_for_the_reports(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertGreaterThan(0, Response::count());

        // Each seeded report gets a short conversation.
        foreach (Report::all() as $report) {
            $this->assertGreaterThan(0, $report->responses()->count());
        }
    }

    public function test_reports_are_spread_over_several_months_for_the_trend_chart(): void
    {
        $this->seed(DatabaseSeeder::class);

        $months = Report::query()
            ->selectRaw("strftime('%Y-%m', created_at) as month")
            ->groupBy('month')
            ->pluck('month');

        $this->assertGreaterThanOrEqual(
            3,
            $months->count(),
            'Reports should span at least three months so the analytics trend is meaningful.'
        );
    }

    public function test_the_seeded_reports_use_indonesian_content(): void
    {
        $this->seed(DatabaseSeeder::class);

        $titles = Report::pluck('title');

        // No leftover English placeholder text from the old factory defaults.
        $this->assertFalse($titles->contains(fn (string $title): bool => str_contains($title, 'Lorem ipsum')));

        foreach ($titles as $title) {
            $this->assertDoesNotMatchRegularExpression('/Bell Bottom Blues/', $title);
        }
    }

    public function test_the_seeder_is_idempotent(): void
    {
        $this->seed(DatabaseSeeder::class);

        $counts = [
            'users' => User::count(),
            'categories' => Category::count(),
            'facilities' => Facility::count(),
            'reports' => Report::count(),
            'responses' => Response::count(),
        ];

        $this->seed(DatabaseSeeder::class);

        $this->assertSame($counts['users'], User::count());
        $this->assertSame($counts['categories'], Category::count());
        $this->assertSame($counts['facilities'], Facility::count());
        $this->assertSame($counts['reports'], Report::count());
        $this->assertSame($counts['responses'], Response::count());
    }

    public function test_the_report_seeder_skips_gracefully_without_users(): void
    {
        // No users exist, so the report seeder should warn instead of failing.
        $this->seed(CategorySeeder::class);
        $this->seed(FacilitySeeder::class);
        $this->seed(ReportSeeder::class);

        $this->assertSame(0, Report::count());
    }
}
