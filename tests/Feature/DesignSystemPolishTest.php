<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Audit follow-ups for the generated-dashboard tells and hierarchy
 * (taste-skill app-screens.md): no icon-in-a-tinted-chip KPI, sentence-case
 * table headers, and a role-specific page title.
 */
class DesignSystemPolishTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_kpi_cards_do_not_use_the_icon_chip(): void
    {
        $html = $this->actingAs(User::factory()->siswa()->create())
            ->get('/dashboard')
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('stat-icon', $html, 'KPI icon chips should be gone.');

        $css = file_get_contents(resource_path('css/pages/dashboard.css'));
        $this->assertStringNotContainsString('.stat-icon', $css, 'The icon-chip CSS should be removed.');
    }

    public function test_kpi_status_colour_rides_on_a_dot(): void
    {
        $html = $this->actingAs(User::factory()->siswa()->create())
            ->get('/dashboard')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('stat-dot', $html);

        $css = file_get_contents(resource_path('css/pages/dashboard.css'));
        $this->assertStringContainsString('.stat-card-pending .stat-dot', $css);
        $this->assertStringContainsString('.stat-card-resolved .stat-dot', $css);
    }

    public function test_table_headers_are_not_uppercase_or_tracked(): void
    {
        // Uppercase + letter-spacing reads louder than the data it labels
        // (app-screens.md 7.2), and 10px is below the legible minimum.
        foreach ([
            'css/pages/admin.css' => '.admin-table th',
            'css/pages/dashboard.css' => '.reports-table th',
            'css/pages/discussion-index.css' => '.discussion-table th',
        ] as $file => $selector) {
            $css = file_get_contents(resource_path($file));

            preg_match('/'.preg_quote($selector, '/').'\s*\{(.*?)\}/s', $css, $m);
            $this->assertNotEmpty($m, "$selector not found in $file.");

            $this->assertStringNotContainsString('uppercase', $m[1], "$selector must not be uppercase.");
            $this->assertStringNotContainsString('letter-spacing', $m[1], "$selector must not be tracked.");
            $this->assertStringContainsString('font-size: 12px', $m[1], "$selector should be 12px.");
        }
    }

    public function test_each_role_gets_a_specific_page_title(): void
    {
        $titles = [
            'siswa' => 'Laporan Saya',
            'petugas' => 'Antrean Laporan',
            'admin' => 'Ringkasan Fasilitas',
        ];

        foreach ($titles as $role => $expected) {
            $user = User::factory()->create(['role' => $role]);

            $this->actingAs($user)
                ->get('/dashboard')
                ->assertOk()
                ->assertSee($expected);
        }
    }
}
