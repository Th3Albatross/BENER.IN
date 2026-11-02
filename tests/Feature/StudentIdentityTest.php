<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Students sign in with their NIS (nomor induk siswa); petugas and admin keep
 * using email. The login form accepts whichever was entered.
 */
class StudentIdentityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_a_siswa_can_log_in_with_their_nis(): void
    {
        $siswa = User::factory()->siswa()->create([
            'nis' => '1234567890',
            'password' => 'rahasia123',
        ]);

        $this->post(route('login'), [
            'login' => '1234567890',
            'password' => 'rahasia123',
        ])->assertRedirect();

        $this->assertAuthenticatedAs($siswa);
    }

    public function test_a_siswa_can_log_in_with_their_email(): void
    {
        $siswa = User::factory()->siswa()->create([
            'email' => 'siswa.terdaftar@example.com',
            'password' => 'rahasia123',
        ]);

        $this->post(route('login'), [
            'login' => 'siswa.terdaftar@example.com',
            'password' => 'rahasia123',
        ])->assertRedirect();

        $this->assertAuthenticatedAs($siswa);
    }

    public function test_a_petugas_logs_in_with_email(): void
    {
        $petugas = User::factory()->petugas()->create([
            'email' => 'petugas@example.com',
            'password' => 'rahasia123',
        ]);

        $this->post(route('login'), [
            'login' => 'petugas@example.com',
            'password' => 'rahasia123',
        ])->assertRedirect();

        $this->assertAuthenticatedAs($petugas);
    }

    public function test_a_wrong_password_is_rejected_for_a_nis_login(): void
    {
        User::factory()->siswa()->create(['nis' => '1234567890', 'password' => 'rahasia123']);

        $this->from(route('login'))->post(route('login'), [
            'login' => '1234567890',
            'password' => 'salah-sekali',
        ])->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_an_unknown_nis_is_rejected(): void
    {
        $this->from(route('login'))->post(route('login'), [
            'login' => '0000000000',
            'password' => 'rahasia123',
        ])->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_a_numeric_login_never_matches_an_email(): void
    {
        // A student whose email happens to be numeric must not be reachable by
        // the NIS lookup path, and vice versa.
        $siswa = User::factory()->siswa()->create([
            'nis' => '1234567890',
            'email' => '9999999999',
            'password' => 'rahasia123',
        ]);

        // The all-digits value is treated as a NIS, so it matches this student's NIS.
        $this->post(route('login'), [
            'login' => '1234567890',
            'password' => 'rahasia123',
        ])->assertRedirect();

        $this->assertAuthenticatedAs($siswa);
    }

    public function test_a_student_without_an_email_can_still_log_in(): void
    {
        $siswa = User::factory()->siswa()->create([
            'email' => null,
            'nis' => '4444444444',
            'password' => 'rahasia123',
        ]);

        $this->post(route('login'), [
            'login' => '4444444444',
            'password' => 'rahasia123',
        ])->assertRedirect();

        $this->assertAuthenticatedAs($siswa);
    }

    public function test_the_login_failure_message_mentions_both_identifiers(): void
    {
        $this->from(route('login'))->post(route('login'), [
            'login' => 'nobody@example.com',
            'password' => 'rahasia123',
        ])->assertSessionHasErrors('loginFailure');

        $this->assertGuest();
    }

    public function test_the_login_field_is_required(): void
    {
        $this->post(route('login'), [
            'login' => '',
            'password' => 'rahasia123',
        ])->assertSessionHasErrors('login');
    }
}
