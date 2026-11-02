<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Printing must always use the light palette.
 *
 * Charts and the report page read colour tokens, so without an explicit print
 * override a user in dim/dark mode would export a dark PDF (or light-on-white
 * chart strokes).
 */
class PrintPaletteTest extends TestCase
{
    private function themeCss(): string
    {
        return file_get_contents(resource_path('css/theme.css'));
    }

    private function printBlock(): string
    {
        $css = $this->themeCss();
        $start = strpos($css, '@media print');

        $this->assertNotFalse($start, 'theme.css has no @media print block.');

        return substr($css, $start);
    }

    public function test_the_print_block_overrides_every_theme_variant(): void
    {
        $print = $this->printBlock();

        foreach (['light', 'dim', 'dark'] as $mode) {
            $this->assertStringContainsString(
                "[data-theme='$mode']",
                $print,
                "The [$mode] mode is not forced to the light palette when printing."
            );
        }
    }

    public function test_print_surfaces_and_text_are_light(): void
    {
        $print = $this->printBlock();

        foreach ([
            '--surface: #ffffff',
            '--surface-2: #ffffff',
            '--bg: #ffffff',
            '--text-strong: #1a1a1a',
            '--border: #dddddd',
        ] as $declaration) {
            $this->assertStringContainsString($declaration, $print, "Missing print override: $declaration");
        }

        // No dark surfaces should leak into the print palette.
        $this->assertStringNotContainsString('--surface: #1e1f22', $print);
        $this->assertStringNotContainsString('--surface: #313338', $print);
    }

    public function test_print_shadows_are_removed_to_save_ink(): void
    {
        $print = $this->printBlock();

        $this->assertStringContainsString('--shadow-card: none', $print);
        $this->assertStringContainsString('--shadow-raised: none', $print);
    }

    /**
     * Every token that differs between modes must be re-declared for print,
     * otherwise that one property keeps its dark value on the PDF.
     */
    public function test_every_mode_dependent_token_is_overridden_for_print(): void
    {
        // Parse only the mode declarations; the print block also mentions
        // [data-theme] selectors and would otherwise be read as a mode.
        $css = substr($this->themeCss(), 0, strpos($this->themeCss(), '@media print'));

        preg_match_all("/\[data-theme='(\w+)'\]\s*\{(.*?)\n\}/s", $css, $matches, PREG_SET_ORDER);

        $byMode = [];
        foreach ($matches as $match) {
            preg_match_all('/(--[a-z0-9-]+):\s*([^;]+);/', $match[2], $tokens, PREG_SET_ORDER);
            foreach ($tokens as $token) {
                $byMode[$match[1]][$token[1]] = trim($token[2]);
            }
        }

        $light = $byMode['light'];
        $print = $this->printBlock();
        preg_match_all('/(--[a-z0-9-]+):/', $print, $overridden);
        $overridden = $overridden[1];

        // The sidebar is hidden when printing, so its dark tokens are exempt.
        $irrelevant = [
            '--sidebar', '--sidebar-border', '--sidebar-active',
            '--sidebar-text', '--sidebar-muted', '--sidebar-hover',
        ];

        $missing = [];
        foreach ($light as $token => $value) {
            if (in_array($token, $irrelevant, true)) {
                continue;
            }

            $differs = ($byMode['dim'][$token] ?? null) !== $value
                || ($byMode['dark'][$token] ?? null) !== $value;

            if ($differs && ! in_array($token, $overridden, true)) {
                $missing[] = $token;
            }
        }

        $this->assertSame(
            [],
            $missing,
            'These tokens keep their dark value when printing: '.implode(', ', $missing)
        );
    }

    public function test_the_page_level_print_blocks_do_not_use_dark_tokens(): void
    {
        // Both used a token (or the dark --surface) that resolved dark in dark mode.
        $analytics = file_get_contents(resource_path('css/pages/analytics.css'));
        $analyticsPrint = substr($analytics, strpos($analytics, '@media print'));

        $this->assertStringContainsString('background:#ffffff', $analyticsPrint);
        $this->assertStringNotContainsString('background:var(--surface)', $analyticsPrint);

        $report = file_get_contents(resource_path('css/pages/report-show.css'));
        $reportPrint = substr($report, strpos($report, '@media print'));

        $this->assertStringContainsString('background:#fff', $reportPrint);
    }

    public function test_charts_are_re_rendered_for_print(): void
    {
        // Canvas colours are baked in at draw time, so CSS alone cannot fix them.
        foreach (['js/analytics.js', 'js/admin-dashboard.js'] as $module) {
            $js = file_get_contents(resource_path($module));

            $this->assertStringContainsString("addEventListener('beforeprint'", $js, "$module must redraw before printing.");
            $this->assertStringContainsString("addEventListener('afterprint'", $js, "$module must restore after printing.");
            $this->assertStringContainsString('chart.destroy()', $js, "$module must clear instances so a redraw picks up new colours.");
        }
    }

    /**
     * An animation in flight is captured half-drawn, which is what made the
     * exported charts look broken. The print redraw must be synchronous.
     */
    public function test_charts_do_not_animate_while_printing(): void
    {
        foreach (['js/analytics.js', 'js/admin-dashboard.js'] as $module) {
            $js = file_get_contents(resource_path($module));

            $this->assertMatchesRegularExpression(
                '/animation:\s*isPrint\s*\?\s*false/',
                $js,
                "$module must disable animation for the print redraw."
            );
        }
    }

    public function test_the_print_redraw_passes_the_print_flag(): void
    {
        foreach (['js/analytics.js', 'js/admin-dashboard.js'] as $module) {
            $js = file_get_contents(resource_path($module));

            $this->assertMatchesRegularExpression(
                "/beforeprint',\s*\(\)\s*=>\s*[a-zA-Z]+\([^)]*true\)/",
                $js,
                "$module must call its chart builder with the print flag on beforeprint."
            );
        }
    }

    /**
     * Chart.js writes inline px width/height from the screen size. On the
     * narrower print page that overflows and clips the chart unless the
     * canvases are pinned to their containers.
     */
    public function test_the_chart_pages_constrain_canvases_when_printing(): void
    {
        $analytics = file_get_contents(resource_path('css/pages/analytics.css'));
        $analyticsPrint = substr($analytics, strpos($analytics, '@media print'));

        foreach (['.analytics-trend-canvas', '.analytics-status-canvas', '.analytics-bar-canvas'] as $container) {
            $this->assertStringContainsString($container.' canvas', $analyticsPrint, "$container canvas is not constrained for print.");
        }

        $admin = file_get_contents(resource_path('css/pages/admin.css'));
        $adminPrint = substr($admin, strpos($admin, '@media print'));

        $this->assertStringContainsString('.admin-chart-canvas canvas', $adminPrint);
    }

    /**
     * Print media is not reliably active when `beforeprint` fires, so reading
     * the CSS tokens there can still yield the dark on-screen palette. The print
     * colours must be fixed.
     */
    public function test_the_print_palette_is_hard_coded_not_read_from_tokens(): void
    {
        foreach (['js/analytics.js', 'js/admin-dashboard.js'] as $module) {
            $js = file_get_contents(resource_path($module));

            $this->assertStringContainsString('PRINT_COLORS', $js, "$module must define a print palette.");

            // Extract only the PRINT_COLORS object literal.
            $matched = preg_match('/PRINT_COLORS = \{(.*?)\n\};/s', $js, $m);
            $this->assertSame(1, $matched, "$module print palette could not be parsed.");

            $palette = $m[1];
            // The print palette uses literals, never token() lookups.
            $this->assertStringNotContainsString('token(', $palette, "$module print palette must not read CSS tokens.");

            // Dark ink on paper, never the light text used by dark themes.
            $this->assertStringContainsString("ink: '#1a1a1a'", $palette, "$module must print with dark ink.");
        }
    }

    public function test_the_admin_dashboard_has_a_print_stylesheet(): void
    {
        // It printed the sidebar and the screen grid before this block existed.
        $admin = file_get_contents(resource_path('css/pages/admin.css'));
        $adminPrint = substr($admin, strpos($admin, '@media print'));

        $this->assertNotFalse(strpos($admin, '@media print'), 'admin.css has no print block.');
        $this->assertStringContainsString('.sidebar', $adminPrint);
        $this->assertStringContainsString('.app-shell', $adminPrint);
    }
}
