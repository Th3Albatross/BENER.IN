<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\Cookie;
use Tests\TestCase;

/**
 * "Ingat saya" (remember me) on the login page.
 *
 * The original implementation was broken:
 *   Auth::attempt($credentials, $credentials['remember'] ??= false)
 * The flag was read from the validated credentials array, which never contains
 * it, so remember-me silently never activated. It now reads the request.
 */
class RememberMeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    private function recallerFrom($response): ?Cookie
    {
        foreach ($response->headers->getCookies() as $cookie) {
            if (str_starts_with($cookie->getName(), 'remember_')) {
                return $cookie;
            }
        }

        return null;
    }

    private function loginWithRemember(User $user, bool $remember): Cookie
    {
        $response = $this->post(route('login'), [
            'login' => $user->email,
            'password' => 'rahasia123',
            'remember' => $remember ? '1' : '0',
        ])->assertRedirect();

        $cookie = $this->recallerFrom($response);
        $this->assertNotNull($cookie, 'A remember-me cookie should have been issued.');

        return $cookie;
    }

    /** Drop the session and cached guards so only the cookie can authenticate. */
    private function forgetSession(): void
    {
        $this->flushSession();
        $this->app['auth']->forgetGuards();
    }

    public function test_the_remember_checkbox_activates_a_persistent_cookie(): void
    {
        $user = User::factory()->siswa()->create(['password' => 'rahasia123']);

        $this->loginWithRemember($user, true);

        $this->assertAuthenticatedAs($user);
    }

    public function test_no_persistent_cookie_is_issued_without_the_checkbox(): void
    {
        $user = User::factory()->siswa()->create(['password' => 'rahasia123']);

        $response = $this->post(route('login'), [
            'login' => $user->email,
            'password' => 'rahasia123',
        ])->assertRedirect();

        $this->assertNull($this->recallerFrom($response));
    }

    public function test_the_cookie_reauthenticates_after_the_session_is_lost(): void
    {
        $user = User::factory()->siswa()->create(['password' => 'rahasia123']);

        $cookie = $this->loginWithRemember($user, true);

        // A brand-new browser session: only the persistent cookie remains.
        $this->forgetSession();

        $this->withUnencryptedCookie($cookie->getName(), $cookie->getValue())
            ->get('/dashboard')
            ->assertOk();

        $this->assertAuthenticatedAs($user->fresh());
    }

    public function test_without_the_checkbox_a_lost_session_logs_the_user_out(): void
    {
        $user = User::factory()->siswa()->create(['password' => 'rahasia123']);

        $this->post(route('login'), [
            'login' => $user->email,
            'password' => 'rahasia123',
        ])->assertRedirect();

        $this->forgetSession();

        $this->get('/dashboard')->assertRedirect(route('login'));
    }

    public function test_logging_out_rotates_the_token_so_the_old_cookie_is_dead(): void
    {
        $user = User::factory()->siswa()->create(['password' => 'rahasia123']);

        $cookie = $this->loginWithRemember($user, true);
        $tokenBefore = $user->fresh()->remember_token;

        $this->withUnencryptedCookie($cookie->getName(), $cookie->getValue())->get('/logout');

        // The stored token must change, otherwise the captured cookie would
        // keep working after the user signed out.
        $this->assertNotSame($tokenBefore, $user->fresh()->remember_token);

        $this->forgetSession();

        $this->withUnencryptedCookie($cookie->getName(), $cookie->getValue())
            ->get('/dashboard')
            ->assertRedirect(route('login'));
    }

    public function test_a_cookie_is_rejected_after_the_password_changes(): void
    {
        $user = User::factory()->siswa()->create(['password' => 'rahasia123']);

        $cookie = $this->loginWithRemember($user, true);

        $user->update(['password' => 'password-baru-sekali']);

        $this->forgetSession();

        $this->withUnencryptedCookie($cookie->getName(), $cookie->getValue())
            ->get('/dashboard')
            ->assertRedirect(route('login'));
    }
}
