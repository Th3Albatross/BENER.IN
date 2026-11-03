<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Two accessibility/data-visualisation fixes from the UI/UX audit:
 *  - --text-muted failed WCAG AA in light mode (3.66-4.07:1);
 *  - High and Medium severity badges used identical tokens, so the triage
 *    signal collapsed four levels into three visible states.
 */
class ContrastAndSeverityTest extends TestCase
{
    private function themeCss(): string
    {
        return file_get_contents(resource_path('css/theme.css'));
    }

    /** Parse one theme block's tokens. `light` is the :root block. */
    private function tokens(string $mode): array
    {
        $css = $this->themeCss();
        $selector = $mode === 'light' ? ':root,' : "[data-theme='$mode']";
        $start = strpos($css, $selector);

        $this->assertNotFalse($start, "Theme block for $mode not found.");

        $block = substr($css, $start);
        $end = strpos($block, '}');
        $block = substr($block, 0, $end);

        preg_match_all('/(--[a-z0-9-]+):\s*([^;]+);/', $block, $m, PREG_SET_ORDER);

        $out = [];
        foreach ($m as $row) {
            $out[$row[1]] = trim($row[2]);
        }

        return $out;
    }

    /** WCAG 2.x relative luminance contrast ratio for two hex colours. */
    private function ratio(string $hexA, string $hexB): float
    {
        $lum = function (string $hex): float {
            $hex = ltrim($hex, '#');
            $channels = array_map(function ($pair) {
                $c = hexdec($pair) / 255;

                return $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
            }, str_split($hex, 2));

            return 0.2126 * $channels[0] + 0.7152 * $channels[1] + 0.0722 * $channels[2];
        };

        $a = $lum($hexA);
        $b = $lum($hexB);

        return (max($a, $b) + 0.05) / (min($a, $b) + 0.05);
    }

    public function test_muted_text_meets_aa_on_every_light_surface(): void
    {
        $light = $this->tokens('light');
        $muted = $light['--text-muted'];

        foreach (['--surface', '--surface-2', '--bg'] as $token) {
            $ratio = $this->ratio($muted, $light[$token]);

            $this->assertGreaterThanOrEqual(
                4.5,
                $ratio,
                "Muted text ($muted) on $token ({$light[$token]}) is only ".round($ratio, 2).':1; AA needs 4.5:1.'
            );
        }
    }

    public function test_muted_text_meets_aa_in_dim_and_dark(): void
    {
        foreach (['dim', 'dark'] as $mode) {
            $t = $this->tokens($mode);

            foreach (['--surface', '--surface-2', '--bg'] as $token) {
                $ratio = $this->ratio($t['--text-muted'], $t[$token]);

                $this->assertGreaterThanOrEqual(
                    4.5,
                    $ratio,
                    "$mode: muted text on $token is only ".round($ratio, 2).':1.'
                );
            }
        }
    }

    public function test_high_and_medium_severity_are_visually_distinct(): void
    {
        $css = file_get_contents(resource_path('css/pages/dashboard.css'));

        // High uses the solid fill; Medium keeps the soft tint.
        preg_match('/\.severity-high\s*\{(.*?)\}/s', $css, $high);
        preg_match('/\.severity-medium\s*\{(.*?)\}/s', $css, $medium);

        $this->assertNotEmpty($high, 'severity-high rule missing.');
        $this->assertNotEmpty($medium, 'severity-medium rule missing.');

        $this->assertStringContainsString('--warning-fill', $high[1], 'High must use the solid warning fill.');
        $this->assertStringNotContainsString('--warning-fill', $medium[1], 'Medium must stay on the soft tint.');

        // And they must not resolve to the same background.
        $this->assertNotSame(
            trim($high[1]),
            trim($medium[1]),
            'High and Medium severity still share identical styling.'
        );
    }

    public function test_high_severity_fill_is_legible_in_every_mode(): void
    {
        foreach (['light', 'dim', 'dark'] as $mode) {
            $t = $this->tokens($mode);

            foreach (['--warning-fill', '--warning-fill-fg'] as $token) {
                $this->assertArrayHasKey($token, $t, "$mode is missing $token.");
            }

            $ratio = $this->ratio($t['--warning-fill-fg'], $t['--warning-fill']);

            $this->assertGreaterThanOrEqual(
                4.5,
                $ratio,
                "$mode: the High badge text on its fill is only ".round($ratio, 2).':1.'
            );
        }
    }

    public function test_the_print_palette_also_carries_the_warning_fill(): void
    {
        $css = $this->themeCss();
        $print = substr($css, strpos($css, '@media print'));

        $this->assertStringContainsString('--warning-fill:', $print);
        $this->assertStringContainsString('--warning-fill-fg:', $print);
    }
}
