<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Facility;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Admin redesign: the dashboard answers "is the master data complete and used"
 * rather than only counting rows, and the list screens gain a visible filter,
 * a search box and a usage ranking.
 */
class AdminRedesignTest extends TestCase
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

    public function test_the_dashboard_reuses_the_shared_stat_card_tiles(): void
    {
        $html = $this->actingAs($this->admin())->get('/dashboard')->assertOk()->getContent();

        // Same KPI component as the siswa/petugas dashboards.
        $this->assertStringContainsString('dashboard-stats admin-stats', $html);
        $this->assertSame(5, substr_count($html, 'class="stat-card"'), 'Five KPI tiles expected.');
        $this->assertStringContainsString('stat-card-link', $html);

        // The bespoke admin strip is gone.
        $this->assertStringNotContainsString('admin-kpi', $html);
    }

    public function test_the_dashboard_reports_master_data_coverage(): void
    {
        $admin = $this->admin();
        User::factory()->siswa()->create(['nis' => '1000000001', 'classroom' => 'XI-1']);
        User::factory()->siswa()->create(['nis' => null, 'classroom' => null]);

        $html = $this->actingAs($admin)->get('/dashboard')->assertOk()->getContent();

        $this->assertStringContainsString('Kelengkapan Data', $html);
        $this->assertStringContainsString('Siswa dengan NIS', $html);
        $this->assertStringContainsString('Siswa dengan kelas', $html);
        $this->assertStringContainsString('Fasilitas terpakai', $html);
        $this->assertStringContainsString('Kategori terpakai', $html);
        $this->assertStringContainsString('admin-coverage-fill', $html);
    }

    public function test_the_dashboard_names_the_worst_data_gap_as_the_finding(): void
    {
        $admin = $this->admin();
        User::factory()->siswa()->count(2)->create(['nis' => null, 'classroom' => null]);

        $view = $this->actingAs($admin)->get('/dashboard')->assertOk();

        $gaps = collect($view->viewData('_dataGaps'))->pluck('count', 'key');

        $this->assertSame(2, $gaps['siswa_tanpa_nis']);
        $this->assertSame(2, $gaps['siswa_tanpa_kelas']);
        $this->assertStringContainsString('Perlu ditindaklanjuti', $view->viewData('_finding'));
    }

    public function test_the_finding_is_positive_when_there_are_no_gaps(): void
    {
        $admin = $this->admin();
        // A complete student and a used facility leave no gaps.
        $student = User::factory()->siswa()->create(['nis' => '1000000001', 'classroom' => 'XI-1']);
        $facility = Facility::factory()->create();
        $category = Category::factory()->create();
        Report::factory()->create([
            'users_id' => $student->id,
            'facilities_id' => $facility->id,
            'categories_id' => $category->id,
        ]);

        $view = $this->actingAs($admin)->get('/dashboard')->assertOk();

        $this->assertSame([], $view->viewData('_dataGaps'));
        $this->assertSame('Semua data induk sekolah sudah lengkap.', $view->viewData('_finding'));
    }

    public function test_the_coverage_percentages_are_computed_from_the_counts(): void
    {
        $admin = $this->admin();
        User::factory()->siswa()->count(4)->sequence(fn ($sequence) => ['nis' => '10000000'.(10 + $sequence->index)])->create(['classroom' => 'XI-1']);
        User::factory()->siswa()->create(['nis' => null, 'classroom' => null]);

        $coverage = collect($this->actingAs($admin)->get('/dashboard')->viewData('_coverage'));

        $nis = $coverage->firstWhere('label', 'Siswa dengan NIS');

        $this->assertSame(4, $nis['done']);
        $this->assertSame(5, $nis['total']);
        $this->assertSame(80, $nis['pct']);
    }

    public function test_the_dashboard_adds_a_students_per_classroom_chart(): void
    {
        $admin = $this->admin();
        User::factory()->siswa()->count(3)->create(['classroom' => 'XI-1']);
        User::factory()->siswa()->count(2)->create(['classroom' => 'X-1']);

        $chart = collect($this->actingAs($admin)->get('/dashboard')->viewData('_studentsByClassroom'));

        $this->assertSame('XI-1', $chart->first()['label']);
        $this->assertSame(3, $chart->first()['total']);

        $html = $this->actingAs($admin)->get('/dashboard')->getContent();
        $this->assertStringContainsString('admin-classroom-chart', $html);
        $this->assertStringContainsString('Siswa per Kelas', $html);
    }

    public function test_the_users_page_shows_role_tabs_with_counts_and_search(): void
    {
        $admin = $this->admin();
        User::factory()->siswa()->count(3)->create(['name' => 'Siswa Uji']);
        User::factory()->petugas()->count(2)->create(['name' => 'Petugas Uji']);

        $html = $this->actingAs($admin)->get(route('admin.users.index'))->assertOk()->getContent();

        $this->assertStringContainsString('admin-tabs', $html);
        $this->assertStringContainsString('admin-tab--active', $html);
        $this->assertStringContainsString('name="q"', $html);

        // Search narrows the list.
        $filtered = $this->actingAs($admin)
            ->get(route('admin.users.index', ['q' => 'Petugas Uji']))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Petugas Uji', $filtered);
        $this->assertStringNotContainsString('Siswa Uji', $filtered);
    }

    public function test_the_facilities_page_shows_a_usage_bar_and_can_sort_by_usage(): void
    {
        $admin = $this->admin();
        $reporter = User::factory()->siswa()->create();
        $busy = Facility::factory()->create(['name' => 'Aula']);
        Facility::factory()->create(['name' => 'Gudang']);
        Report::factory()->count(2)->create(['users_id' => $reporter->id, 'facilities_id' => $busy->id]);

        $html = $this->actingAs($admin)->get(route('admin.facilities.index'))->assertOk()->getContent();

        $this->assertStringContainsString('admin-usage-fill', $html);
        $this->assertStringContainsString('Pemakaian', $html);
        $this->assertStringContainsString('Belum pernah dilaporkan', $html);

        $sorted = $this->actingAs($admin)
            ->get(route('admin.facilities.index', ['sort' => 'reports']))
            ->assertOk()
            ->viewData('facilities');

        $this->assertSame('Aula', $sorted->first()->name);
    }

    public function test_the_facility_and_category_subtitles_render_literally_correct(): void
    {
        $admin = $this->admin();
        Facility::factory()->create(['name' => 'Aula']);
        Category::factory()->create(['name' => 'Kelistrikan']);

        $facilities = $this->actingAs($admin)->get(route('admin.facilities.index'))->assertOk()->getContent();
        $this->assertStringContainsString('fasilitas terdaftar', $facilities);
        $this->assertStringContainsString('belum pernah dilaporkan.', $facilities);
        $this->assertStringNotContainsString('@if', $facilities, 'A Blade directive leaked into the output.');

        $categories = $this->actingAs($admin)->get(route('admin.categories.index'))->assertOk()->getContent();
        $this->assertStringContainsString('kategori terdaftar', $categories);
        $this->assertStringNotContainsString('@if', $categories, 'A Blade directive leaked into the output.');
    }

    public function test_the_list_sort_uses_the_shared_select_form(): void
    {
        $admin = $this->admin();
        Facility::factory()->create(['name' => 'Aula']);
        Category::factory()->create(['name' => 'Kelistrikan']);

        foreach (['admin.facilities.index', 'admin.categories.index'] as $route) {
            $html = $this->actingAs($admin)->get(route($route))->assertOk()->getContent();

            // Same labelled-select pattern as the siswa/petugas dashboards, not URL tabs.
            $this->assertStringContainsString('admin-sort-form', $html, $route);
            $this->assertStringContainsString('Urutkan berdasarkan', $html, $route);
            $this->assertStringContainsString('name="sort"', $html, $route);
            $this->assertStringContainsString('Terapkan', $html, $route);
            $this->assertStringNotContainsString('admin-sort-row', $html, $route);
        }
    }

    public function test_the_categories_page_reports_unused_categories(): void
    {
        $admin = $this->admin();
        Category::factory()->create(['name' => 'Kelistrikan']);

        $view = $this->actingAs($admin)->get(route('admin.categories.index'))->assertOk();

        $this->assertSame(1, $view->viewData('unusedCount'));
        $this->assertStringContainsString('belum pernah dilaporkan', $view->getContent());
        $this->assertStringContainsString('admin-usage-fill', $view->getContent());
    }
}
