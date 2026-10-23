<?php

namespace Tests\Feature;

use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The print stylesheet must neutralise the screen breakpoints: an A4 page with
 * 12mm margins is only ~703 CSS px wide, so the max-width:1100px and 900px
 * rules would otherwise apply and stack the sidebar into a cramped grid.
 */
class ReportPrintLayoutTest extends TestCase
{
    use RefreshDatabase;

    private function printBlock(): string
    {
        $css = file_get_contents(resource_path('css/pages/report-show.css'));
        $start = strpos($css, '@media print');

        $this->assertNotFalse($start, 'The report stylesheet has no @media print block.');

        return substr($css, $start);
    }

    public function test_the_print_block_resets_the_screen_breakpoint_layouts(): void
    {
        $print = $this->printBlock();

        // Layout containers that the screen breakpoints rearrange.
        foreach ([
            '.report-show-grid',
            '.report-show-side',
            '.report-show-main-column',
            '.report-show-hero',
            '.report-show-photo-grid',
            '.report-show-photo',
        ] as $selector) {
            $this->assertStringContainsString(
                $selector,
                $print,
                "$selector is not reset for print and will inherit its screen layout."
            );
        }
    }

    public function test_the_print_block_forces_a_single_column_grid(): void
    {
        $this->assertMatchesRegularExpression(
            '/\.report-show-grid\s*\{[^}]*display:\s*block\s*!important/',
            $this->printBlock(),
            'The print grid should collapse to a single column.'
        );
    }

    public function test_photos_get_an_explicit_height_and_contain_fit_for_print(): void
    {
        $print = $this->printBlock();

        $this->assertStringContainsString('height: 210px !important', $print);
        $this->assertStringContainsString('object-fit: contain !important', $print);
    }

    public function test_cards_stop_clipping_content_when_the_page_breaks(): void
    {
        $this->assertMatchesRegularExpression(
            '/\.report-show-card,\s*\n\s*\.report-show-hero\s*\{\s*overflow:\s*visible\s*!important/',
            $this->printBlock(),
            'Cards must not use overflow:hidden in print or content gets clipped.'
        );
    }

    public function test_the_detail_page_still_renders_for_a_student(): void
    {
        $student = User::factory()->siswa()->create();
        $report = Report::factory()->create(['users_id' => $student->id, 'status' => 'Pending']);

        $this->actingAs($student)
            ->get(route('reports.showDetail', ['uuid' => $report->identifier]))
            ->assertOk()
            ->assertSee('Ekspor PDF');
    }

    public function test_the_print_button_waits_for_lazy_images_before_printing(): void
    {
        $student = User::factory()->siswa()->create();
        $report = Report::factory()->create(['users_id' => $student->id, 'status' => 'Pending']);

        $html = $this->actingAs($student)
            ->get(route('reports.showDetail', ['uuid' => $report->identifier]))
            ->assertOk()
            ->getContent();

        // Photos are lazy-loaded, so the handler must await them first.
        $this->assertStringContainsString('waitForImages', $html);
        $this->assertStringContainsString('loading="lazy"', $html);
    }
}
