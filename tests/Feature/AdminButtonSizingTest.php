<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Admin action buttons must look consistent at every viewport.
 *
 * Regression: a blanket `.admin-button { width: 100% }` inside the mobile media
 * query stretched the dashboard-list "Ubah" link across the row, and in tables
 * it stretched only the anchor while the <form>-wrapped "Hapus" button stayed
 * small, so the two actions no longer matched.
 */
class AdminButtonSizingTest extends TestCase
{
    /** @var array<string, string> selector => declaration block, comments stripped */
    private array $rules = [];

    protected function setUp(): void
    {
        parent::setUp();

        $css = preg_replace('!/\*.*?\*/!s', '', file_get_contents(resource_path('css/pages/admin.css'))) ?? '';

        // Only top-level / media-nested rules (no nested @supports here), which
        // is all this stylesheet uses.
        preg_match_all('/([^{}]+)\{([^{}]*)\}/', $css, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $selectorList = trim($match[1]);
            $body = trim($match[2]);

            // Skip the media query opening itself; it has no own declarations.
            if (str_starts_with($selectorList, '@media')) {
                continue;
            }

            foreach (explode(',', $selectorList) as $selector) {
                $this->rules[trim($selector)] = $body;
            }
        }
    }

    private function declarations(string $selector): string
    {
        $this->assertArrayHasKey($selector, $this->rules, "Missing rule for `$selector`.");

        return $this->rules[$selector];
    }

    public function test_no_bare_admin_button_rule_forces_full_width(): void
    {
        if (array_key_exists('.admin-button', $this->rules)) {
            $this->assertStringNotContainsString(
                'width: 100%',
                $this->rules['.admin-button'],
                'A bare `.admin-button { width: 100% }` desyncs table and list buttons.'
            );
        }
    }

    public function test_row_and_list_buttons_hug_their_label(): void
    {
        $this->assertStringContainsString('width: auto', $this->declarations('.admin-table-actions .admin-button'));
        $this->assertStringContainsString('width: auto', $this->declarations('.admin-list-item .admin-button'));
    }

    public function test_the_delete_form_wrapper_shrink_wraps(): void
    {
        $this->assertStringContainsString('display: inline-flex', $this->declarations('.admin-table-actions form'));
        $this->assertStringContainsString('margin: 0', $this->declarations('.admin-list-item form'));
    }

    public function test_stacked_form_buttons_stay_full_width_on_mobile(): void
    {
        // These declarations live inside the mobile media query but the flat
        // parser above still records the last matching rule for the selector.
        $source = file_get_contents(resource_path('css/pages/admin.css'));
        $mobile = substr($source, strpos($source, '@media (max-width: 640px)'));

        $this->assertMatchesRegularExpression(
            '/\.admin-form-actions\s+\.admin-button[^{]*\{\s*width:\s*100%/s',
            $mobile,
            'Create/edit form buttons should remain full width when stacked.'
        );
    }
}
