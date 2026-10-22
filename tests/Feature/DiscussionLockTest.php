<?php

namespace Tests\Feature;

use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DiscussionLockTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_concluded_discussion_is_read_only_in_the_ui(): void
    {
        $student = User::factory()->create(['role' => 'siswa']);
        $report = Report::factory()->create([
            'users_id' => $student->id,
            'status' => 'Resolved',
            'severity' => 'Low',
        ]);
        $report->responses()->create([
            'users_id' => $student->id,
            'message' => 'Catatan sebelum laporan selesai.',
        ]);

        $this->actingAs($student)
            ->get(route('responses.show', ['uuid' => $report->identifier]))
            ->assertOk()
            ->assertSee('Catatan sebelum laporan selesai.')
            ->assertSee('Diskusi ditutup')
            ->assertSee('hanya dapat dibaca')
            ->assertDontSee('Kirim Pesan')
            ->assertDontSee('<button type="button" class="discussion-edit-trigger"', false);
    }

    public function test_concluded_discussion_rejects_new_messages_and_edits(): void
    {
        $student = User::factory()->create(['role' => 'siswa']);
        $report = Report::factory()->create([
            'users_id' => $student->id,
            'status' => 'Rejected',
            'severity' => 'Low',
        ]);
        $response = $report->responses()->create([
            'users_id' => $student->id,
            'message' => 'Pesan asli.',
        ]);

        $this->actingAs($student)
            ->post(route('responses.store', ['uuid' => $report->identifier]), [
                'message' => 'Pesan baru yang tidak boleh masuk.',
            ])
            ->assertForbidden();

        $this->actingAs($student)
            ->patch(route('responses.update', [
                'uuid' => $report->identifier,
                'responseId' => $response->id,
            ]), [
                'message' => 'Pesan yang diubah.',
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('responses', [
            'id' => $response->id,
            'message' => 'Pesan asli.',
        ]);
    }
}
