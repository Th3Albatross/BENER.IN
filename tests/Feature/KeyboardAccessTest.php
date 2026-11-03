<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Audit follow-ups: a keyboard user can skip the navigation, and sign-out no
 * longer borrows the danger red that means Kritis/Ditolak/Hapus elsewhere.
 */
class KeyboardAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_a_skip_link_targets_the_main_content(): void
    {
        $html = $this->actingAs(User::factory()->siswa()->create())
            ->get('/dashboard')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('class="skip-link"', $html);
        $this->assertStringContainsString('href="#main-content"', $html);
        $this->assertStringContainsString('id="main-content"', $html);
        $this->assertStringContainsString('Lewati ke konten', $html);
    }

    public function test_the_skip_link_is_the_first_focusable_element(): void
    {
        $html = $this->actingAs(User::factory()->siswa()->create())
            ->get('/dashboard')
            ->assertOk()
            ->getContent();

        // It must come before the sidebar, so Tab reaches it first.
        $this->assertLessThan(
            strpos($html, 'class="sidebar"'),
            strpos($html, 'class="skip-link"'),
            'The skip link must precede the navigation in document order.'
        );
    }

    public function test_main_content_is_programmatically_focusable(): void
    {
        $html = $this->actingAs(User::factory()->siswa()->create())
            ->get('/dashboard')
            ->assertOk()
            ->getContent();

        // tabindex="-1" lets the skip link move focus into <main>.
        $this->assertMatchesRegularExpression('/<main[^>]*id="main-content"[^>]*tabindex="-1"/', $html);
    }

    public function test_the_skip_link_is_hidden_until_focused(): void
    {
        $css = file_get_contents(resource_path('css/layouts/app.css'));

        // Clipped rather than display:none, which would make it unfocusable.
        $this->assertStringContainsString('.skip-link', $css);
        $this->assertMatchesRegularExpression('/\.skip-link\s*\{[^}]*clip-path:\s*inset\(50%\)/s', $css);
        $this->assertStringNotContainsString('.skip-link { display: none', $css);
        $this->assertMatchesRegularExpression('/\.skip-link:focus-visible\s*\{/', $css);
    }

    public function test_logout_does_not_use_the_danger_colour(): void
    {
        $css = file_get_contents(resource_path('css/layouts/app.css'));

        preg_match('/\.sidebar__logout\s*\{(.*?)\}/s', $css, $m);

        $this->assertNotEmpty($m, 'The .sidebar__logout rule is missing.');
        $this->assertStringNotContainsString(
            '--danger',
            $m[1],
            'Sign-out is routine; danger red is reserved for Kritis/Ditolak/Hapus.'
        );
    }

    public function test_logout_still_renders_with_its_accessible_name(): void
    {
        $html = $this->actingAs(User::factory()->siswa()->create())
            ->get('/dashboard')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('class="sidebar__logout"', $html);
        $this->assertStringContainsString('data-theme-reset', $html);
        $this->assertStringContainsString('Keluar dari akun', $html);
    }
}
