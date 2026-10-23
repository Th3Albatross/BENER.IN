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
        $analytics = file_get_contents(resource_path('css/analytics.css'));
        $analyticsPrint = substr($analytics, strpos($analytics, '@media print'));

        $this->assertStringContainsString('background:#ffffff', $analyticsPrint);
        $this->assertStringNotContainsString('background:var(--surface)', $analyticsPrint);

        $report = file_get_contents(resource_path('css/report-show.css'));
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
}
