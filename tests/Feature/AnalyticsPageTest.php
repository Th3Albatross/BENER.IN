<?php

namespace Tests\Feature;

use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // The page pulls assets through @vite; tests do not need a built manifest.
        $this->withoutVite();
    }

    private function userWithRole(string $role): User
    {
        return User::factory()->create(['role' => $role]);
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/analytics')->assertRedirect(route('login'));
    }

    public function test_students_cannot_open_the_analytics_page(): void
    {
        $this->actingAs($this->userWithRole('siswa'))
            ->get('/analytics')
            ->assertForbidden();
    }

    public function test_staff_see_the_summary_and_insights_for_the_default_period(): void
    {
        $student = $this->userWithRole('siswa');

        Report::factory()->create([
            'users_id' => $student->id,
            'categories_id' => null,
            'status' => 'Pending',
            'severity' => 'High',
        ]);

        $this->actingAs($this->userWithRole('petugas'))
            ->get('/analytics')
            ->assertOk()
            ->assertSee('Web Analytics')
            ->assertDontSee('Manajemen Laporan')
            ->assertViewHas('summary', fn (array $summary): bool => $summary['total'] === 1 && $summary['pending'] === 1)
            ->assertViewHas('insights', fn (array $insights): bool => $insights['urgent_open'] === 1);
    }

    public function test_a_custom_period_requires_both_dates(): void
    {
        $this->actingAs($this->userWithRole('petugas'))
            ->get('/analytics?period=custom')
            ->assertSessionHasErrors(['start_date', 'end_date']);
    }
}
