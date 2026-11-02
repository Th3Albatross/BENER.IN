<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Phone layout for the login page: the decorative hero-card is hidden so the
 * intro headline flows straight into the form. Desktop and tablet keep it.
 */
class LoginHeroResponsiveTest extends TestCase
{
    private function css(): string
    {
        // Comments stripped so prose in a comment can't satisfy an assertion.
        $css = file_get_contents(resource_path('css/pages/auth-login.css'));

        return preg_replace('!/\*.*?\*/!s', '', $css) ?? $css;
    }

    /** The contents of the `@media (max-width: 600px) { ... }` block. */
    private function phoneBlock(): string
    {
        $css = $this->css();
        $start = strpos($css, '@media (max-width: 600px)');

        $this->assertNotFalse($start, 'The phone media query is missing.');

        $block = substr($css, $start);
        $depth = 0;
        $end = 0;

        for ($i = 0, $len = strlen($block); $i < $len; $i++) {
            if ($block[$i] === '{') {
                $depth++;
            } elseif ($block[$i] === '}') {
                $depth--;
                if ($depth === 0) {
                    $end = $i;
                    break;
                }
            }
        }

        return substr($block, 0, $end + 1);
    }

    public function test_the_hero_is_hidden_on_phones(): void
    {
        $this->assertMatchesRegularExpression(
            '/\.hero-card\s*\{[^}]*display:\s*none/s',
            $this->phoneBlock(),
            'The decorative hero must be hidden on phones so the form moves up.'
        );
    }

    public function test_the_hero_keeps_its_sizing_outside_the_phone_breakpoint(): void
    {
        $css = $this->css();
        $tablet = substr($css, strpos($css, '@media (max-width: 920px)'), strpos($css, '@media (max-width: 600px)') - strpos($css, '@media (max-width: 920px)'));

        // Desktop/tablet still give the hero a real height and no hiding rule.
        $this->assertMatchesRegularExpression('/\.hero-card\s*\{[^}]*min-height:\s*\d+px/s', $css);
        $this->assertStringNotContainsString('display: none', $tablet);
    }

    public function test_the_login_form_still_follows_the_intro_panel(): void
    {
        $markup = file_get_contents(resource_path('views/auth/login.blade.php'));

        // Order matters: hiding the hero lets the form naturally move up.
        $this->assertLessThan(
            strpos($markup, 'class="form-panel"'),
            strpos($markup, 'class="intro-panel"'),
            'The intro panel must precede the form panel.'
        );
    }
}
