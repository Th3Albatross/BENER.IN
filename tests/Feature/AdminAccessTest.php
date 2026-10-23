<?php

namespace Tests\Feature;

use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAccessTest extends TestCase
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

    private function petugas(): User
    {
        return User::factory()->petugas()->create();
    }

    private function siswa(): User
    {
        return User::factory()->siswa()->create();
    }

    private function reportFor(User $reporter): Report
    {
        return Report::factory()->create([
            'users_id' => $reporter->id,
            'status' => 'Pending',
            'severity' => 'Low',
        ]);
    }

    public function test_admin_is_not_treated_as_staff(): void
    {
        $admin = $this->admin();

        $this->assertFalse($admin->isStaff());
        $this->assertTrue($admin->isAdmin());
        $this->assertFalse($admin->isStudent());
    }

    public function test_petugas_and_siswa_are_never_admin(): void
    {
        $this->assertFalse($this->petugas()->isAdmin());
        $this->assertFalse($this->siswa()->isAdmin());
        $this->assertTrue($this->petugas()->isStaff());
        $this->assertTrue($this->siswa()->isStudent());
    }

    public function test_admin_lands_on_the_management_dashboard(): void
    {
        $this->actingAs($this->admin())
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Komposisi Pengguna')
            ->assertDontSee('Seluruh Laporan');
    }

    public function test_petugas_still_gets_the_staff_dashboard(): void
    {
        $this->actingAs($this->petugas())
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Seluruh Laporan');
    }

    public function test_siswa_still_gets_the_student_dashboard(): void
    {
        $this->actingAs($this->siswa())
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Daftar Laporan');
    }

    public function test_admin_cannot_open_any_report_page(): void
    {
        $report = $this->reportFor($this->siswa());
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('reports.showCreate'))->assertForbidden();
        $this->actingAs($admin)->post(route('reports.create'), [])->assertForbidden();
        $this->actingAs($admin)->get(route('reports.showDetail', ['uuid' => $report->identifier]))->assertForbidden();
        $this->actingAs($admin)->get(route('reports.showEdit', ['uuid' => $report->identifier]))->assertForbidden();
        $this->actingAs($admin)->put(route('reports.update', ['uuid' => $report->identifier]), [])->assertForbidden();
    }

    public function test_admin_cannot_change_a_report_status(): void
    {
        $report = $this->reportFor($this->siswa());

        $this->actingAs($this->admin())
            ->patch(route('reports.updateStatus', ['uuid' => $report->identifier]), ['status' => 'Resolved'])
            ->assertForbidden();

        $this->assertDatabaseHas('reports', ['id' => $report->id, 'status' => 'Pending']);
    }

    public function test_admin_cannot_access_discussions(): void
    {
        $report = $this->reportFor($this->siswa());
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('discussions.index'))->assertForbidden();
        $this->actingAs($admin)->get(route('responses.show', ['uuid' => $report->identifier]))->assertForbidden();
        $this->actingAs($admin)->post(route('responses.store', ['uuid' => $report->identifier]), ['message' => 'hai'])->assertForbidden();
    }

    public function test_admin_cannot_open_analytics(): void
    {
        $this->actingAs($this->admin())->get(route('analytics.index'))->assertForbidden();
    }

    public function test_petugas_and_siswa_cannot_reach_admin_routes(): void
    {
        foreach ([$this->petugas(), $this->siswa()] as $user) {
            $this->actingAs($user)->get(route('admin.users.index'))->assertForbidden();
            $this->actingAs($user)->get(route('admin.users.create'))->assertForbidden();
            $this->actingAs($user)->get(route('admin.facilities.index'))->assertForbidden();
            $this->actingAs($user)->get(route('admin.categories.index'))->assertForbidden();
        }
    }

    public function test_guests_are_redirected_from_admin_routes(): void
    {
        $this->get(route('admin.users.index'))->assertRedirect(route('login'));
    }

    public function test_admin_sidebar_shows_only_management_navigation(): void
    {
        $html = $this->actingAs($this->admin())->get('/dashboard')->assertOk()->getContent();

        $this->assertStringContainsString('Pengguna', $html);
        $this->assertStringContainsString('Fasilitas', $html);
        $this->assertStringContainsString('Kategori', $html);

        // Admin has no report or discussion navigation.
        $this->assertStringNotContainsString('Buat Laporan', $html);
        $this->assertStringNotContainsString('Diskusi', $html);
        $this->assertStringNotContainsString('Analytics', $html);
    }

    public function test_petugas_sidebar_hides_admin_navigation(): void
    {
        $html = $this->actingAs($this->petugas())->get('/dashboard')->assertOk()->getContent();

        // Scope to the sidebar: the dashboard body legitimately mentions "Kategori".
        $sidebar = $this->sidebarMarkup($html);

        $this->assertStringNotContainsString('admin/categories', $sidebar);
        $this->assertStringNotContainsString('admin/users', $sidebar);
        $this->assertStringNotContainsString('admin/facilities', $sidebar);
        $this->assertStringNotContainsString('Buat Laporan', $sidebar);
        $this->assertStringContainsString('Diskusi', $sidebar);
    }

    /** Extract just the <aside class="sidebar"> block from a rendered page. */
    private function sidebarMarkup(string $html): string
    {
        $start = strpos($html, '<aside class="sidebar"');
        $end = strpos($html, '</aside>');

        $this->assertNotFalse($start, 'Sidebar not found in the rendered page.');
        $this->assertNotFalse($end, 'Sidebar closing tag not found.');

        return substr($html, $start, $end - $start);
    }
}
