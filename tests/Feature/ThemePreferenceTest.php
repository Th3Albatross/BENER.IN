<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ThemePreferenceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_the_theme_selector_is_rendered_on_the_settings_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('settings.index'))
            ->assertOk()
            ->assertSee('Mode tampilan')
            ->assertSee('Terang')
            ->assertSee('Redup')
            ->assertSee('Gelap')
            ->assertSee('data-theme-choice', false);
    }

    public function test_the_selector_offers_all_three_modes_with_previews(): void
    {
        $html = $this->actingAs(User::factory()->create())
            ->get(route('settings.index'))
            ->assertOk()
            ->getContent();

        foreach (['light', 'dim', 'dark'] as $mode) {
            $this->assertStringContainsString('settings-theme-preview--'.$mode, $html);
            $this->assertStringContainsString('value="'.$mode.'"', $html);
        }
    }

    public function test_the_theme_choice_is_not_persisted_to_the_database(): void
    {
        // The preference is a per-device client concern: no column, no endpoint.
        $this->assertFalse(
            Schema::hasColumn('users', 'theme'),
            'The users table should not carry a theme column.'
        );

        $this->assertFalse(
            Route::has('settings.theme.update'),
            'Theme selection should not hit a backend endpoint.'
        );
    }

    public function test_the_theme_selector_does_not_post_a_form(): void
    {
        $html = $this->actingAs(User::factory()->create())
            ->get(route('settings.index'))
            ->assertOk()
            ->getContent();

        // Extract the appearance card and confirm it contains no form submission.
        $cardStart = strpos($html, 'appearance-heading');
        $this->assertNotFalse($cardStart);

        $card = substr($html, $cardStart, 3000);
        $this->assertStringNotContainsString('<form', $card);
    }

    public function test_the_theme_stylesheet_is_loaded_on_the_layout_and_login_page(): void
    {
        foreach (['layouts/app.blade.php', 'auth/login.blade.php'] as $view) {
            $this->assertStringContainsString(
                'resources/css/theme.css',
                file_get_contents(resource_path('views/'.$view))
            );
        }
    }

    public function test_the_theme_module_is_loaded_by_the_layout(): void
    {
        $this->assertStringContainsString(
            'resources/js/theme.js',
            file_get_contents(resource_path('views/layouts/app.blade.php'))
        );
    }

    public function test_logging_out_resets_the_stored_theme(): void
    {
        $sidebar = file_get_contents(resource_path('views/layouts/sidebar.blade.php'));
        $module = file_get_contents(resource_path('js/theme.js'));

        // The logout link is flagged so the module can clear the stored mode.
        $this->assertStringContainsString('data-theme-reset', $sidebar);

        // The module removes the key rather than leaving it for the next person.
        $this->assertStringContainsString('removeItem(STORAGE_KEY)', $module);
        $this->assertStringContainsString('data-theme-reset', $module);
    }

    public function test_both_entry_points_apply_the_stored_theme_before_paint(): void
    {
        foreach (['layouts/app.blade.php', 'auth/login.blade.php'] as $view) {
            $contents = file_get_contents(resource_path('views/'.$view));

            $this->assertStringContainsString('benerin:theme', $contents);
            $this->assertStringContainsString('localStorage.getItem', $contents);
            // The bootstrap must sit in <head> so it runs before the body paints.
            $this->assertLessThan(
                strpos($contents, '</head>'),
                strpos($contents, 'localStorage.getItem'),
                "The theme bootstrap in $view should run before </head>."
            );
        }
    }

    public function test_every_mode_defines_the_same_token_set(): void
    {
        // Only the mode declarations; the trailing @media print block also
        // mentions [data-theme] selectors and would otherwise be parsed as one.
        $css = $this->modeDeclarationCss();

        preg_match_all("/\[data-theme='(\w+)'\]\s*\{(.*?)\n\}/s", $css, $blocks, PREG_SET_ORDER);

        $tokensByMode = [];
        foreach ($blocks as $block) {
            preg_match_all('/(--[a-z0-9-]+):/', $block[2], $tokens);
            $tokensByMode[$block[1]] = $tokens[1];
        }

        $this->assertSame(['light', 'dim', 'dark'], array_keys($tokensByMode));

        // Every mode must expose an identical set so no token silently falls back.
        foreach ($tokensByMode as $mode => $tokens) {
            $this->assertEqualsCanonicalizing(
                $tokensByMode['light'],
                $tokens,
                "Mode [$mode] does not define the same tokens as light."
            );
        }
    }

    /** theme.css up to (but excluding) the print override block. */
    private function modeDeclarationCss(): string
    {
        $css = file_get_contents(resource_path('css/theme.css'));
        $printStart = strpos($css, '@media print');

        return $printStart === false ? $css : substr($css, 0, $printStart);
    }

    /**
     * A button that paints white text must never sit on a token that turns
     * light in dark mode (the original "Simpan Foto" bug).
     */
    public function test_no_stylesheet_paints_white_text_on_a_light_switching_token(): void
    {
        $offenders = [];
        $files = array_merge(
            glob(resource_path('css/*.css')),
            glob(resource_path('css/layouts/*.css'))
        );

        foreach ($files as $file) {
            if (basename($file) === 'theme.css') {
                continue;
            }

            $css = file_get_contents($file);

            preg_match_all('/([^{}]+)\{([^{}]*)\}/', $css, $rules, PREG_SET_ORDER);

            foreach ($rules as $rule) {
                $body = $rule[2];

                if (! preg_match('/(?<!-)color:\s*(#fff\b|#ffffff\b)/', $body, $fg)) {
                    continue;
                }

                if (! preg_match('/background(?:-color)?:\s*([^;]+)/', $body, $bg)) {
                    continue;
                }

                // These tokens are light in dim/dark, so white text disappears.
                if (preg_match('/var\(--(accent|accent-strong|text|text-strong|text-muted|text-soft)\b/', $bg[1])) {
                    $offenders[] = basename($file).': '.trim($fg[0]).' on '.trim($bg[1]);
                }
            }
        }

        $this->assertSame([], $offenders, 'White text on a light-switching background: '.implode(' | ', $offenders));
    }

    /**
     * Buttons that carry white text should use the dedicated fill tokens, which
     * stay dark enough in every mode.
     */
    public function test_the_accent_fill_tokens_keep_white_text_readable_in_every_mode(): void
    {
        $css = $this->modeDeclarationCss();

        preg_match_all("/\[data-theme='(\w+)'\]\s*\{(.*?)\n\}/s", $css, $blocks, PREG_SET_ORDER);
        $this->assertNotEmpty($blocks);

        foreach ($blocks as $block) {
            $mode = $block[1];
            preg_match('/--accent-fill:\s*(#[0-9a-fA-F]{6})/', $block[2], $fill);
            preg_match('/--accent-fill-fg:\s*(#[0-9a-fA-F]{6})/', $block[2], $fg);

            $this->assertNotEmpty($fill, "Mode [$mode] is missing --accent-fill.");
            $this->assertNotEmpty($fg, "Mode [$mode] is missing --accent-fill-fg.");

            $this->assertGreaterThanOrEqual(
                4.5,
                $this->contrastRatio($fg[1], $fill[1]),
                "White text on the accent fill fails contrast in [$mode]."
            );
        }
    }

    /** WCAG relative-luminance contrast ratio between two hex colours. */
    private function contrastRatio(string $foreground, string $background): float
    {
        $luminance = static function (string $hex): float {
            $hex = ltrim($hex, '#');
            $channels = array_map(
                static fn (string $pair): float => hexdec($pair) / 255,
                str_split($hex, 2)
            );

            $linear = array_map(
                static fn (float $channel): float => $channel <= 0.03928
                    ? $channel / 12.92
                    : (($channel + 0.055) / 1.055) ** 2.4,
                $channels
            );

            return 0.2126 * $linear[0] + 0.7152 * $linear[1] + 0.0722 * $linear[2];
        };

        $a = $luminance($foreground);
        $b = $luminance($background);

        return (max($a, $b) + 0.05) / (min($a, $b) + 0.05);
    }
}
