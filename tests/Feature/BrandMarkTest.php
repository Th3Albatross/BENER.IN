<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The BENER.IN brand mark is an inline SVG partial shared by the sidebar and the
 * login header. Serving it as an uploaded file through AttendantController would
 * break caching and couple a public, static asset to private storage.
 */
class BrandMarkTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    private function partial(): string
    {
        return file_get_contents(resource_path('views/components/brand-mark.blade.php'));
    }

    public function test_the_partial_is_an_inline_decorative_svg(): void
    {
        $partial = $this->partial();

        $this->assertStringContainsString('<svg', $partial);
        $this->assertStringContainsString('viewBox="0 0 48 48"', $partial);
        // Decorative: the wrapping link carries the accessible name.
        $this->assertStringContainsString('aria-hidden="true"', $partial);
    }

    public function test_the_partial_draws_the_glyph_on_a_brand_backing(): void
    {
        $partial = $this->partial();

        // Blue rounded-square backing plus the white wrench + screwdriver.
        $this->assertStringContainsString('fill="#3a5cb8"', $partial);
        $this->assertStringContainsString('rx="11"', $partial);
        $this->assertStringContainsString('fill="#ffffff"', $partial);
    }

    public function test_both_entry_points_render_the_shared_mark(): void
    {
        $login = $this->get('/login')->assertOk()->getContent();
        $dashboard = $this->actingAs(User::factory()->siswa()->create())
            ->get('/dashboard')
            ->assertOk()
            ->getContent();

        foreach ([$login, $dashboard] as $html) {
            $this->assertStringContainsString('<svg', $html);
            $this->assertStringContainsString('viewBox="0 0 48 48"', $html);
            $this->assertStringContainsString('fill="#3a5cb8"', $html);
        }
    }

    public function test_the_old_css_drawn_mark_is_gone(): void
    {
        $sources = [
            'views/layouts/sidebar.blade.php' => file_get_contents(resource_path('views/layouts/sidebar.blade.php')),
            'views/auth/login.blade.php' => file_get_contents(resource_path('views/auth/login.blade.php')),
            'css/layouts/app.css' => file_get_contents(resource_path('css/layouts/app.css')),
            'css/pages/auth-login.css' => file_get_contents(resource_path('css/pages/auth-login.css')),
        ];

        foreach ($sources as $name => $contents) {
            foreach (['brand__mark', 'brand-mark-dot', 'brand-mark::'] as $dead) {
                $this->assertStringNotContainsString(
                    $dead,
                    $contents,
                    "$name still references the removed CSS mark ($dead)."
                );
            }
        }
    }

    public function test_the_wordmark_text_still_renders(): void
    {
        $this->get('/login')->assertOk()->assertSee('BENER.IN');
    }

    /* ------------------------------ web icons ------------------------------ */

    public function test_the_icon_files_exist(): void
    {
        // A 0-byte favicon.ico means the tab shows the browser default, which is
        // exactly the bug this covers.
        foreach (['favicon.ico', 'favicon.svg', 'apple-touch-icon.png'] as $file) {
            $path = public_path($file);

            $this->assertFileExists($path, "Missing $file.");
            $this->assertGreaterThan(0, filesize($path), "$file is empty.");
        }
    }

    public function test_the_favicon_is_a_real_icon_not_an_empty_file(): void
    {
        // ICO files start with a 0x00 0x00 0x01 0x00 header.
        $header = file_get_contents(public_path('favicon.ico'), false, null, 0, 4);

        $this->assertSame("\x00\x00\x01\x00", $header, 'favicon.ico is not a valid icon file.');
    }

    public function test_both_entry_points_link_the_icons(): void
    {
        $pages = [
            $this->get('/login')->assertOk()->getContent(),
            $this->actingAs(User::factory()->siswa()->create())->get('/dashboard')->assertOk()->getContent(),
        ];

        foreach ($pages as $html) {
            $this->assertStringContainsString('rel="icon"', $html);
            $this->assertStringContainsString('favicon.ico', $html);
            $this->assertStringContainsString('favicon.svg', $html);
            $this->assertStringContainsString('apple-touch-icon', $html);
        }
    }
}
