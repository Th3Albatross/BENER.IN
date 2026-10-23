<?php

namespace Tests\Feature;

use App\Models\Report;
use App\Models\User;
use App\Services\SidebarNavigation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class SidebarHighlightTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    private function nav(): SidebarNavigation
    {
        return app(SidebarNavigation::class);
    }

    private function sectionFor(string $uri, array $query = []): string
    {
        return $this->nav()->activeSection(Request::create($uri, 'GET', $query));
    }

    /* ------------------------- section resolution ------------------------- */

    public function test_dashboard_and_report_pages_highlight_the_dashboard(): void
    {
        // Reports are opened from the dashboard and have no nav item of their own.
        $this->assertSame(SidebarNavigation::DASHBOARD, $this->sectionFor('/dashboard'));
        $this->assertSame(SidebarNavigation::DASHBOARD, $this->sectionFor('/reports/0198-uuid'));
        $this->assertSame(SidebarNavigation::DASHBOARD, $this->sectionFor('/reports/0198-uuid/edit'));
    }

    public function test_creating_a_report_highlights_its_own_item(): void
    {
        $this->assertSame(SidebarNavigation::REPORTS, $this->sectionFor('/reports/create'));
    }

    public function test_the_discussions_list_highlights_discussions(): void
    {
        $this->assertSame(SidebarNavigation::DISCUSSION, $this->sectionFor('/discussions'));
    }

    /**
     * A per-report discussion keeps the page it was opened from: the list
     * passes ?from=discussions, report detail does not.
     */
    public function test_a_report_discussion_highlights_the_origin_page(): void
    {
        $this->assertSame(
            SidebarNavigation::DASHBOARD,
            $this->sectionFor('/reports/0198-uuid/discussion')
        );

        $this->assertSame(
            SidebarNavigation::DISCUSSION,
            $this->sectionFor('/reports/0198-uuid/discussion', ['from' => 'discussions'])
        );
    }

    public function test_settings_analytics_and_admin_pages_resolve_their_own_sections(): void
    {
        $this->assertSame(SidebarNavigation::SETTINGS, $this->sectionFor('/settings'));
        $this->assertSame(SidebarNavigation::SETTINGS, $this->sectionFor('/settings/password'));
        $this->assertSame(SidebarNavigation::ANALYTICS, $this->sectionFor('/analytics'));
        $this->assertSame(SidebarNavigation::ADMIN_USERS, $this->sectionFor('/admin/users'));
        $this->assertSame(SidebarNavigation::ADMIN_USERS, $this->sectionFor('/admin/users/create'));
        $this->assertSame(SidebarNavigation::ADMIN_FACILITIES, $this->sectionFor('/admin/facilities/3/edit'));
        $this->assertSame(SidebarNavigation::ADMIN_CATEGORIES, $this->sectionFor('/admin/categories'));
    }

    public function test_is_active_matches_only_the_resolved_section(): void
    {
        $request = Request::create('/reports/create', 'GET');

        $this->assertTrue($this->nav()->isActive($request, SidebarNavigation::REPORTS));
        $this->assertFalse($this->nav()->isActive($request, SidebarNavigation::DASHBOARD));
    }

    /* --------------------------- rendered output --------------------------- */

    private function activeItems(string $html): array
    {
        preg_match_all('/class="nav-item nav-item--active"[^>]*>.*?<span>([^<]+)<\/span>/s', $html, $matches);

        return array_map('trim', $matches[1]);
    }

    private function renderDashboardFor(User $user, string $url): string
    {
        return $this->actingAs($user)->get($url)->assertOk()->getContent();
    }

    public function test_exactly_one_item_is_highlighted_on_report_detail(): void
    {
        $student = User::factory()->siswa()->create();
        $report = Report::factory()->create(['users_id' => $student->id, 'status' => 'Pending']);

        $html = $this->renderDashboardFor($student, route('reports.showDetail', ['uuid' => $report->identifier]));

        // This used to highlight nothing at all.
        $this->assertSame(['Dashboard'], $this->activeItems($html));
    }

    public function test_report_edit_still_highlights_the_dashboard(): void
    {
        $student = User::factory()->siswa()->create();
        $report = Report::factory()->create(['users_id' => $student->id, 'status' => 'Pending']);

        $html = $this->renderDashboardFor($student, route('reports.showEdit', ['uuid' => $report->identifier]));

        $this->assertSame(['Dashboard'], $this->activeItems($html));
    }

    public function test_discussion_opened_from_the_dashboard_highlights_the_dashboard(): void
    {
        $student = User::factory()->siswa()->create();
        $report = Report::factory()->create(['users_id' => $student->id, 'status' => 'Pending']);

        $html = $this->renderDashboardFor($student, route('responses.show', ['uuid' => $report->identifier]));

        $this->assertSame(['Dashboard'], $this->activeItems($html));
    }

    public function test_discussion_opened_from_the_list_highlights_discussions(): void
    {
        $student = User::factory()->siswa()->create();
        $report = Report::factory()->create(['users_id' => $student->id, 'status' => 'Pending']);

        $html = $this->renderDashboardFor($student, route('responses.show', [
            'uuid' => $report->identifier,
            'from' => 'discussions',
        ]));

        $this->assertSame(['Diskusi'], $this->activeItems($html));
    }

    public function test_the_discussions_list_highlights_only_discussions(): void
    {
        $petugas = User::factory()->petugas()->create();

        $html = $this->renderDashboardFor($petugas, route('discussions.index'));

        $this->assertSame(['Diskusi'], $this->activeItems($html));
    }

    public function test_each_page_highlights_at_most_one_item(): void
    {
        $petugas = User::factory()->petugas()->create();

        foreach (['/dashboard', '/discussions', '/analytics', '/settings'] as $url) {
            $html = $this->renderDashboardFor($petugas, $url);
            $this->assertLessThanOrEqual(
                1,
                count($this->activeItems($html)),
                "More than one sidebar item is active on $url"
            );
        }
    }
}
