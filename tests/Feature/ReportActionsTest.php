<?php

namespace Tests\Feature;

use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportActionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    private function reportFor(User $reporter): Report
    {
        return Report::factory()->create([
            'users_id' => $reporter->id,
            'status' => 'Pending',
            'severity' => 'Low',
        ]);
    }

    public function test_report_detail_has_browser_pdf_export_for_students(): void
    {
        $student = User::factory()->create(['role' => 'siswa']);
        $report = $this->reportFor($student);

        $this->actingAs($student)
            ->get(route('reports.showDetail', ['uuid' => $report->identifier]))
            ->assertOk()
            ->assertSee('Ekspor PDF')
            ->assertSee('data-report-print', false)
            ->assertDontSee('Ubah status laporan');
    }

    public function test_staff_can_change_a_report_status(): void
    {
        $student = User::factory()->create(['role' => 'siswa']);
        $staff = User::factory()->create(['role' => 'petugas']);
        $report = $this->reportFor($student);

        $this->actingAs($staff)
            ->patch(route('reports.updateStatus', ['uuid' => $report->identifier]), [
                'status' => 'Resolved',
            ])
            ->assertRedirect(route('reports.showDetail', ['uuid' => $report->identifier]))
            ->assertSessionHas('success', 'Status laporan berhasil diubah menjadi Selesai.');

        $this->assertDatabaseHas('reports', [
            'id' => $report->id,
            'status' => 'Resolved',
        ]);

        $this->actingAs($staff)
            ->patch(route('reports.updateStatus', ['uuid' => $report->identifier]), [
                'status' => 'Rejected',
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('reports', [
            'id' => $report->id,
            'status' => 'Resolved',
        ]);
    }

    public function test_students_cannot_change_a_report_status(): void
    {
        $student = User::factory()->create(['role' => 'siswa']);
        $report = $this->reportFor($student);

        $this->actingAs($student)
            ->patch(route('reports.updateStatus', ['uuid' => $report->identifier]), [
                'status' => 'Rejected',
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('reports', [
            'id' => $report->id,
            'status' => 'Pending',
        ]);
    }
}
