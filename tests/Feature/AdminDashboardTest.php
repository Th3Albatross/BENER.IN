<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Facility;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    public function test_the_dashboard_shows_statistic_charts_instead_of_management_links(): void
    {
        $admin = $this->admin();
        $reporter = User::factory()->siswa()->create();
        $facility = Facility::factory()->create();
        $category = Category::factory()->create();
        Report::factory()->create([
            'users_id' => $reporter->id,
            'facilities_id' => $facility->id,
            'categories_id' => $category->id,
        ]);

        $html = $this->actingAs($admin)->get('/dashboard')->assertOk()->getContent();

        // Chart widgets are present.
        $this->assertStringContainsString('Komposisi Pengguna', $html);
        $this->assertStringContainsString('admin-role-chart', $html);
        $this->assertStringContainsString('admin-facility-chart', $html);
        $this->assertStringContainsString('admin-category-chart', $html);

        // The old "Kelola Data" quick-link block is gone.
        $this->assertStringNotContainsString('Kelola Data', $html);
    }

    public function test_the_dashboard_shows_recent_records(): void
    {
        $admin = $this->admin();
        User::factory()->siswa()->create(['name' => 'Siswa Terbaru']);
        Facility::factory()->create(['name' => 'Fasilitas Terbaru']);
        Category::factory()->create(['name' => 'Kategori Terbaru']);

        $this->actingAs($admin)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Pengguna Terbaru')
            ->assertSee('Fasilitas Terbaru')
            ->assertSee('Kategori Terbaru')
            ->assertSee('Siswa Terbaru')
            ->assertSee('Fasilitas Terbaru')
            ->assertSee('Kategori Terbaru');
    }

    public function test_the_role_chart_counts_each_role(): void
    {
        $admin = $this->admin();
        User::factory()->siswa()->count(3)->create();
        User::factory()->petugas()->count(2)->create();

        $chart = $this->actingAs($admin)->get('/dashboard')->assertOk()->viewData('_roleChart');

        $byLabel = collect($chart)->pluck('total', 'label');

        // 3 siswa + 2 petugas + the acting admin.
        $this->assertSame(3, $byLabel['Siswa']);
        $this->assertSame(2, $byLabel['Petugas']);
        $this->assertSame(1, $byLabel['Admin']);
    }

    /**
     * STAFF_ROLES also lists legacy spellings ("attendant", "staff") that the
     * role enum no longer permits, so only "petugas" can ever be counted.
     */
    public function test_the_role_chart_always_reports_all_three_roles(): void
    {
        $admin = $this->admin();
        User::factory()->petugas()->create();

        $chart = $this->actingAs($admin)->get('/dashboard')->assertOk()->viewData('_roleChart');

        $this->assertSame(['Siswa', 'Petugas', 'Admin'], collect($chart)->pluck('label')->all());
        $this->assertSame(1, collect($chart)->firstWhere('label', 'Petugas')['total']);
    }

    public function test_the_facility_and_category_charts_rank_by_report_count(): void
    {
        $admin = $this->admin();
        $reporter = User::factory()->siswa()->create();

        $busy = Facility::factory()->create(['name' => 'Aula']);
        $quiet = Facility::factory()->create(['name' => 'Gudang']);

        $category = Category::factory()->create(['name' => 'Kebersihan']);

        Report::factory()->count(3)->create([
            'users_id' => $reporter->id,
            'facilities_id' => $busy->id,
            'categories_id' => $category->id,
        ]);
        Report::factory()->create([
            'users_id' => $reporter->id,
            'facilities_id' => $quiet->id,
            'categories_id' => $category->id,
        ]);

        $facilityChart = $this->actingAs($admin)->get('/dashboard')->assertOk()->viewData('_facilityChart');
        $categoryChart = $this->actingAs($admin)->get('/dashboard')->assertOk()->viewData('_categoryChart');

        // Highest report count first.
        $this->assertSame('Aula', $facilityChart[0]['label']);
        $this->assertSame(3, $facilityChart[0]['total']);
        $this->assertSame(1, $facilityChart[1]['total']);

        $this->assertSame('Kebersihan', $categoryChart[0]['label']);
        $this->assertSame(4, $categoryChart[0]['total']);
    }

    public function test_the_charts_are_capped_to_six_entries(): void
    {
        $admin = $this->admin();

        Facility::factory()->count(9)->create();
        Category::factory()->count(9)->create();

        $this->actingAs($admin)->get('/dashboard')->assertOk()
            ->assertViewHas('_facilityChart', fn (array $rows): bool => count($rows) === 6)
            ->assertViewHas('_categoryChart', fn (array $rows): bool => count($rows) === 6);
    }

    public function test_empty_data_shows_helpful_states_instead_of_broken_charts(): void
    {
        $html = $this->actingAs($this->admin())->get('/dashboard')->assertOk()->getContent();

        // No reports or master data beyond the acting admin.
        $this->assertStringContainsString('Belum ada laporan', $html);
        $this->assertStringContainsString('Belum ada fasilitas', $html);
        $this->assertStringContainsString('Belum ada kategori', $html);
    }

    public function test_the_chart_payload_is_embedded_as_json(): void
    {
        $admin = $this->admin();
        User::factory()->siswa()->create();
        Facility::factory()->create(['name' => 'Perpustakaan']);

        $html = $this->actingAs($admin)->get('/dashboard')->assertOk()->getContent();

        $this->assertStringContainsString('id="admin-chart-data"', $html);
        $this->assertStringContainsString('Perpustakaan', $html);
    }

    public function test_only_admins_see_the_admin_dashboard(): void
    {
        foreach ([User::factory()->petugas()->create(), User::factory()->siswa()->create()] as $user) {
            $this->actingAs($user)
                ->get('/dashboard')
                ->assertOk()
                ->assertDontSee('Komposisi Pengguna');
        }
    }
}
