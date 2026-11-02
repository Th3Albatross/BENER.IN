<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Self-service email change from the Pengaturan page. Email is a login
 * credential, so a confirmation field guards against a lockout-inducing typo.
 */
class SettingsEmailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_a_user_can_change_their_email(): void
    {
        $user = User::factory()->siswa()->create(['email' => 'lama@example.com']);

        $this->actingAs($user)
            ->patch(route('settings.email.update'), [
                'email' => 'baru@example.com',
                'email_confirmation' => 'baru@example.com',
            ])
            ->assertRedirect(route('settings.index'))
            ->assertSessionHas('status', 'email-updated');

        $this->assertSame('baru@example.com', $user->refresh()->email);
    }

    public function test_the_new_email_must_be_unique(): void
    {
        User::factory()->siswa()->create(['email' => 'dipakai@example.com']);
        $user = User::factory()->siswa()->create(['email' => 'lama@example.com']);

        $this->actingAs($user)
            ->patch(route('settings.email.update'), [
                'email' => 'dipakai@example.com',
                'email_confirmation' => 'dipakai@example.com',
            ])
            ->assertSessionHasErrors('email');

        $this->assertSame('lama@example.com', $user->refresh()->email);
    }

    public function test_the_confirmation_must_match(): void
    {
        $user = User::factory()->siswa()->create(['email' => 'lama@example.com']);

        $this->actingAs($user)
            ->patch(route('settings.email.update'), [
                'email' => 'baru@example.com',
                'email_confirmation' => 'beda@example.com',
            ])
            ->assertSessionHasErrors('email');

        $this->assertSame('lama@example.com', $user->refresh()->email);
    }

    public function test_an_invalid_or_empty_email_is_rejected(): void
    {
        $user = User::factory()->siswa()->create(['email' => 'lama@example.com']);

        $this->actingAs($user)
            ->patch(route('settings.email.update'), [
                'email' => 'bukan-email',
                'email_confirmation' => 'bukan-email',
            ])
            ->assertSessionHasErrors('email');

        $this->actingAs($user)
            ->patch(route('settings.email.update'), [
                'email' => '',
                'email_confirmation' => '',
            ])
            ->assertSessionHasErrors('email');
    }

    public function test_a_user_may_keep_their_own_email(): void
    {
        $user = User::factory()->siswa()->create(['email' => 'sama@example.com']);

        $this->actingAs($user)
            ->patch(route('settings.email.update'), [
                'email' => 'sama@example.com',
                'email_confirmation' => 'sama@example.com',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('sama@example.com', $user->refresh()->email);
    }

    public function test_a_petugas_can_still_log_in_after_changing_their_email(): void
    {
        $petugas = User::factory()->petugas()->create([
            'email' => 'lama@example.com',
            'password' => 'rahasia123',
        ]);

        $this->actingAs($petugas)
            ->patch(route('settings.email.update'), [
                'email' => 'petugas.baru@example.com',
                'email_confirmation' => 'petugas.baru@example.com',
            ])
            ->assertSessionHasNoErrors();

        $this->post('/logout');

        $this->post(route('login'), [
            'login' => 'petugas.baru@example.com',
            'password' => 'rahasia123',
        ])->assertRedirect();

        $this->assertAuthenticatedAs($petugas->fresh());
    }

    public function test_the_profile_card_shows_the_labeled_details(): void
    {
        $user = User::factory()->siswa()->create([
            'nis' => '10000001',
            'classroom' => 'XI-1',
            'email' => 'siswa@benerin.test',
        ]);

        $this->actingAs($user)
            ->get(route('settings.index'))
            ->assertOk()
            ->assertSee('NIS')
            ->assertSee('10000001')
            ->assertSee('Kelas')
            ->assertSee('XI-1')
            ->assertSee('Email')
            ->assertSee('siswa@benerin.test')
            ->assertSee('settings-email-form', false)
            ->assertSee('settings-fact', false);
    }

    public function test_the_card_falls_back_to_placeholders_when_data_is_missing(): void
    {
        $user = User::factory()->petugas()->create([
            'nis' => null,
            'classroom' => null,
        ]);

        $this->actingAs($user)
            ->get(route('settings.index'))
            ->assertOk()
            ->assertSee('Belum tersedia');
    }

    public function test_the_photo_panel_shows_only_the_relevant_buttons(): void
    {
        // A clean disk guarantees no leftover avatar files for this user.
        Storage::fake('local');

        $user = User::factory()->siswa()->create();

        $html = $this->actingAs($user)
            ->get(route('settings.index'))
            ->assertOk()
            ->getContent();

        // Without a photo, "Hapus foto" must not be offered.
        $this->assertMatchesRegularExpression(
            '/id="profile-photo-delete-button"[^>]*\shidden/',
            $html,
            'The delete button must be hidden when there is no photo.'
        );
        $this->assertStringContainsString('id="settings-avatar-initials"', $html);

        // Exactly one button group is visible at rest.
        $this->assertStringContainsString('data-photo-state="idle"', $html);
        $this->assertStringContainsString('data-photo-state="uploading" hidden', $html);
        $this->assertStringContainsString('data-photo-state="confirming-delete" hidden', $html);
    }

    public function test_the_email_form_is_open_again_when_validation_fails(): void
    {
        $user = User::factory()->siswa()->create(['email' => 'lama@example.com']);

        // Follow the redirect so the flashed errors render on the settings page.
        $page = $this->actingAs($user)
            ->from(route('settings.index'))
            ->followingRedirects()
            ->patch(route('settings.email.update'), [
                'email' => 'bukan-email',
                'email_confirmation' => 'bukan-email',
            ])
            ->assertOk()
            ->getContent();

        // The inline form is open and the error is visible without any JS.
        $this->assertStringContainsString('data-open="true"', $page);
        $this->assertStringContainsString('aria-expanded="true"', $page);
        $this->assertStringContainsString('Format email tidak valid.', $page);
    }
}
