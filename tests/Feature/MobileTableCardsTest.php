<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Facility;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The dense data tables are fixed-width on desktop (800-1260px). Below 760px
 * they restack as cards so the row's action button is not pushed off-screen by
 * horizontal scrolling.
 */
class MobileTableCardsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    private function css(): string
    {
        return file_get_contents(resource_path('css/layouts/app.css'));
    }

    /** The mobile card block, comments stripped. */
    private function cardBlock(): string
    {
        $css = $this->css();
        $start = strpos($css, '@media screen and (max-width: 760px)');

        $this->assertNotFalse($start, 'The mobile card block is missing.');

        return preg_replace('!/\*.*?\*/!s', '', substr($css, $start)) ?? '';
    }

    public function test_the_card_layout_is_scoped_to_screens_not_print(): void
    {
        // A4 print is ~703px wide; an unqualified max-width query would restyle
        // the printed tables as cards.
        $this->assertStringContainsString('@media screen and (max-width: 760px)', $this->css());
    }

    public function test_tables_restack_as_cards(): void
    {
        $block = $this->cardBlock();

        foreach (['.reports-table', '.discussion-table', '.admin-table'] as $table) {
            // The selector is scoped through its wrapper for specificity, so the
            // table name is followed by more selector, not the opening brace.
            $this->assertMatchesRegularExpression(
                '/'.$table.'[,\s][^{]*\{[^}]*display:\s*block/s',
                $block,
                "$table must become block-level on mobile."
            );
        }

        // The header row is replaced by per-cell labels.
        $this->assertMatchesRegularExpression('/thead[^{]*\{[^}]*display:\s*none/s', $block);
    }

    public function test_cells_are_labelled_from_data_attributes(): void
    {
        $block = $this->cardBlock();

        $this->assertStringContainsString('content: attr(data-label)', $block);
        $this->assertStringContainsString('::before', $block);
    }

    public function test_row_actions_get_thumb_sized_targets(): void
    {
        $block = $this->cardBlock();

        $this->assertMatchesRegularExpression('/min-height:\s*44px/', $block);
    }

    public function test_every_table_row_cell_carries_a_data_label(): void
    {
        // The card layout prints the column name from data-label; a cell without
        // one would render unlabelled on mobile.
        $admin = User::factory()->admin()->create();
        User::factory()->siswa()->create();
        Facility::factory()->create();
        Category::factory()->create();

        foreach (['admin.users.index', 'admin.facilities.index', 'admin.categories.index'] as $route) {
            $html = $this->actingAs($admin)->get(route($route))->assertOk()->getContent();

            preg_match_all('/<td(\s[^>]*)?>/', $html, $cells);

            $this->assertNotEmpty($cells[0], "$route rendered no table cells.");

            foreach ($cells[0] as $cell) {
                $this->assertStringContainsString(
                    'data-label=',
                    $cell,
                    "$route has a <td> without a data-label, so it would be unlabelled in the mobile card."
                );
            }
        }
    }

    public function test_the_actions_cell_is_labelled_in_the_report_tables(): void
    {
        $siswa = User::factory()->siswa()->create();
        Report::factory()->create(['users_id' => $siswa->id]);

        $html = $this->actingAs($siswa)->get('/dashboard')->assertOk()->getContent();
        $this->assertStringContainsString('data-label="Aksi"', $html);
    }
}
