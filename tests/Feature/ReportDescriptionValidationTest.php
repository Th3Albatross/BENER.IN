<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Facility;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Deskripsi masalah memakai batas 5000 karakter. Melewati batas harus ditolak
 * dengan error validasi, bukan dipotong diam-diam di sisi browser.
 */
class ReportDescriptionValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    /** @return array<string, mixed> */
    private function payload(string $description): array
    {
        return [
            'identifier' => (string) Str::uuid7(),
            'title' => 'Kerusakan lampu kelas',
            'categories_id' => Category::factory()->create()->id,
            'severity' => 'Low',
            'facilities_id' => Facility::factory()->create()->id,
            'location_description' => 'Ruang kelas XI-1',
            'description' => $description,
        ];
    }

    public function test_description_is_not_truncated_at_the_old_3000_limit(): void
    {
        $student = User::factory()->create(['role' => 'siswa']);
        $description = str_repeat('a', 3001);

        $this->actingAs($student)
            ->post(route('reports.create'), $this->payload($description))
            ->assertRedirect('/dashboard');

        $this->assertDatabaseHas('reports', ['description' => $description]);
    }

    public function test_description_over_5000_characters_is_rejected(): void
    {
        $student = User::factory()->create(['role' => 'siswa']);

        $this->actingAs($student)
            ->post(route('reports.create'), $this->payload(str_repeat('a', 5001)))
            ->assertSessionHasErrors('description');

        $this->assertDatabaseCount('reports', 0);
    }

    public function test_description_at_5000_characters_is_accepted(): void
    {
        $student = User::factory()->create(['role' => 'siswa']);
        $description = str_repeat('a', 5000);

        $this->actingAs($student)
            ->post(route('reports.create'), $this->payload($description))
            ->assertRedirect('/dashboard');

        $this->assertDatabaseHas('reports', ['description' => $description]);
    }

    public function test_edit_form_rejects_description_over_5000_characters(): void
    {
        $student = User::factory()->create(['role' => 'siswa']);
        $report = Report::factory()->create([
            'users_id' => $student->id,
            'status' => 'Pending',
            'severity' => 'Low',
        ]);

        $this->actingAs($student)
            ->put(route('reports.update', ['uuid' => $report->identifier]), [
                'title' => 'Judul baru',
                'severity' => 'Low',
                'facilities_id' => $report->facilities_id,
                'location_description' => 'Ruang kelas XI-1',
                'description' => str_repeat('a', 5001),
            ])
            ->assertSessionHasErrors('description');
    }
}
