<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The mobile sidebar: a sticky bar with a dropdown panel that overlays the page.
 * Desktop must be completely unaffected.
 */
class SidebarResponsiveTest extends TestCase
{
    use RefreshDatabase;

    private function css(): string
    {
        return file_get_contents(resource_path('css/layouts/app.css'));
    }

    private function mobileBlock(): string
    {
        $css = $this->css();
        $start = strpos($css, '/*'."\n".' * Mobile: the sidebar becomes a compact sticky bar');

        $this->assertNotFalse($start, 'The mobile sidebar block is missing.');

        return substr($css, $start);
    }

    /** Everything before the first mobile media query: the desktop rules. */
    private function desktopCss(): string
    {
        $css = $this->css();

        return substr($css, 0, strpos($css, '   Responsive'));
    }

    /** CSS with comments removed, so prose in a comment can't satisfy an assertion. */
    private function uncommented(string $css): string
    {
        return preg_replace('!/\*.*?\*/!s', '', $css) ?? $css;
    }

    private function js(): string
    {
        return file_get_contents(resource_path('js/sidebar.js'));
    }

    private function sidebarMarkup(): string
    {
        return file_get_contents(resource_path('views/layouts/sidebar.blade.php'));
    }

    /* --------------------------- markup structure --------------------------- */

    public function test_the_sidebar_has_a_bar_a_panel_and_a_toggle(): void
    {
        $markup = $this->sidebarMarkup();

        $this->assertStringContainsString('class="sidebar__bar"', $markup);
        $this->assertStringContainsString('class="sidebar__panel"', $markup);
        $this->assertStringContainsString('data-sidebar-toggle', $markup);
        $this->assertStringContainsString('data-sidebar', $markup);
    }

    public function test_the_toggle_is_wired_for_accessibility(): void
    {
        $markup = $this->sidebarMarkup();

        $this->assertStringContainsString('aria-expanded="false"', $markup);
        $this->assertStringContainsString('aria-controls="sidebar-panel"', $markup);
        // The panel is the element the toggle controls.
        $this->assertStringContainsString('id="sidebar-panel"', $markup);
    }

    public function test_the_scrim_is_rendered_in_the_layout(): void
    {
        $layout = file_get_contents(resource_path('views/layouts/app.blade.php'));

        $this->assertStringContainsString('data-sidebar-scrim', $layout);
        $this->assertStringContainsString('sidebar-scrim', $layout);
    }

    /* ------------------------ requirements: mobile ------------------------ */

    public function test_the_panel_overlays_content_instead_of_shifting_it(): void
    {
        $mobile = $this->mobileBlock();

        // Absolutely positioned, so it cannot reflow the main content.
        $this->assertMatchesRegularExpression(
            '/\.sidebar__panel\s*\{[^}]*position:\s*absolute/',
            $mobile,
            'The panel must be absolutely positioned so it overlays the page.'
        );
        $this->assertMatchesRegularExpression('/\.sidebar__panel\s*\{[^}]*top:\s*100%/s', $mobile);
    }

    public function test_the_bar_sticks_to_the_viewport_while_scrolling(): void
    {
        $mobile = $this->mobileBlock();

        $this->assertMatchesRegularExpression(
            '/\.sidebar\s*\{[^}]*position:\s*sticky/s',
            $mobile,
            'The mobile bar must be sticky so it follows the scroll position.'
        );
        $this->assertMatchesRegularExpression('/\.sidebar\s*\{[^}]*top:\s*0/s', $mobile);
        $this->assertMatchesRegularExpression('/\.sidebar\s*\{[^}]*z-index:\s*\d+/s', $mobile);
    }

    /**
     * Regression: the desktop rule sets height:100vh. If the mobile block does
     * not reset it, the bar paints a full-viewport solid panel above the
     * content and the whole page looks blank.
     */
    public function test_the_mobile_bar_resets_the_desktop_height(): void
    {
        $mobile = $this->uncommented($this->mobileBlock());

        $this->assertMatchesRegularExpression(
            '/\.sidebar\s*\{[^}]*?height:\s*auto/s',
            $mobile,
            'The mobile bar must reset the desktop height:100vh to height:auto.'
        );
    }

    /**
     * Regression: the dropdown sits at top:100%, outside the bar's box. Any
     * non-visible overflow on .sidebar clips it, so the menu never appears.
     */
    public function test_the_mobile_bar_does_not_clip_the_dropdown(): void
    {
        $mobile = $this->uncommented($this->mobileBlock());

        $this->assertMatchesRegularExpression(
            '/\.sidebar\s*\{[^}]*?overflow:\s*visible/s',
            $mobile,
            'The mobile bar must reset the desktop overflow so the panel is visible.'
        );
    }

    /** Every desktop-only sizing property must be reset in the mobile block. */
    public function test_the_mobile_block_resets_all_desktop_sizing(): void
    {
        $mobile = $this->uncommented($this->mobileBlock());
        $rule = null;

        if (preg_match('/\.sidebar\s*\{([^}]*)\}/', $mobile, $m)) {
            $rule = $m[1];
        }

        $this->assertNotNull($rule, 'Missing the mobile .sidebar rule.');

        foreach (['height: auto', 'min-height: 0', 'max-height: none', 'overflow: visible'] as $declaration) {
            $this->assertStringContainsString(
                $declaration,
                $rule,
                "Mobile .sidebar is missing `$declaration`; the desktop value would carry over."
            );
        }
    }

    public function test_the_panel_is_collapsed_until_toggled(): void
    {
        $mobile = $this->mobileBlock();

        // Closed state: hidden and non-interactive.
        $this->assertMatchesRegularExpression('/\.sidebar__panel\s*\{[^}]*visibility:\s*hidden/s', $mobile);
        $this->assertMatchesRegularExpression('/\.sidebar__panel\s*\{[^}]*pointer-events:\s*none/s', $mobile);

        // Open state is driven by the attribute the script sets on <body>.
        $this->assertStringContainsString('body[data-sidebar-open] .sidebar__panel', $mobile);
    }

    public function test_the_panel_scrolls_inside_the_viewport(): void
    {
        $mobile = $this->mobileBlock();

        // Long nav lists scroll within the panel instead of overflowing the page.
        $this->assertMatchesRegularExpression('/\.sidebar__panel\s*\{[^}]*overflow-y:\s*auto/s', $mobile);
        $this->assertStringContainsString('100dvh', $mobile);
    }

    /* ------------------------ requirements: desktop ------------------------ */

    public function test_desktop_shows_the_toggle_as_hidden(): void
    {
        // The toggle is display:none outside the mobile breakpoint, so a desktop
        // user never sees it and can never open a panel.
        $this->assertMatchesRegularExpression(
            '/\.sidebar__toggle\s*\{\s*display:\s*none;\s*\}/',
            $this->desktopCss(),
            'The toggle must be hidden on desktop.'
        );
    }

    public function test_desktop_sidebar_rules_are_not_overridden_globally(): void
    {
        $desktop = $this->desktopCss();

        // Desktop keeps the pinned, independently scrolling sidebar.
        $this->assertMatchesRegularExpression('/\.sidebar\s*\{[^}]*position:\s*sticky/s', $desktop);
        $this->assertMatchesRegularExpression('/\.sidebar\s*\{[^}]*overflow-y:\s*auto/s', $desktop);

        // The panel is a plain container on desktop: no absolute overlay.
        $panelRule = null;
        if (preg_match('/\.sidebar__panel\s*\{([^}]*)\}/', $desktop, $m)) {
            $panelRule = $m[1];
        }

        $this->assertNotNull($panelRule, 'Missing the desktop .sidebar__panel rule.');
        $this->assertStringNotContainsString('position: absolute', $panelRule);
        $this->assertStringNotContainsString('visibility: hidden', $panelRule);
    }

    public function test_the_scrim_is_inert_on_desktop(): void
    {
        $this->assertMatchesRegularExpression(
            '/\.sidebar-scrim\s*\{\s*display:\s*none;\s*\}/',
            $this->desktopCss()
        );
    }

    /* ------------------------------ behaviour ------------------------------ */

    public function test_the_script_only_opens_on_mobile_viewports(): void
    {
        $js = $this->js();

        // Guarded so a desktop resize can never leave the page locked.
        $this->assertStringContainsString("'(max-width: 700px)'", $js);
        $this->assertStringContainsString('isMobile()', $js);
        $this->assertStringContainsString('setOpen(false)', $js);
    }

    public function test_the_script_closes_on_escape_scrim_and_navigation(): void
    {
        $js = $this->js();

        $this->assertStringContainsString("event.key === 'Escape'", $js);
        $this->assertStringContainsString('scrim', $js);
        $this->assertStringContainsString("closest('a')", $js);
    }

    public function test_the_script_syncs_aria_state(): void
    {
        $js = $this->js();

        $this->assertStringContainsString('aria-expanded', $js);
        $this->assertStringContainsString('aria-label', $js);
    }

    public function test_the_module_is_registered_and_loaded(): void
    {
        $this->assertStringContainsString(
            'resources/js/sidebar.js',
            file_get_contents(resource_path('views/layouts/app.blade.php'))
        );
        $this->assertStringContainsString(
            'resources/js/sidebar.js',
            file_get_contents(base_path('vite.config.js'))
        );
    }

    public function test_every_page_still_renders_the_navigation(): void
    {
        $user = User::factory()->siswa()->create();

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('data-sidebar-toggle', false)
            ->assertSee('sidebar__panel', false)
            ->assertSee('Navigasi utama');
    }
}
