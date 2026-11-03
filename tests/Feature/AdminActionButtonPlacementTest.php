<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The admin master-data pages place their "Tambah ..." action inside the table
 * card header, matching the student/petugas tables, instead of in the page
 * header above the card.
 */
class AdminActionButtonPlacementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    /** @return array<string, array{0: string, 1: string, 2: string}> */
    private function pages(): array
    {
        return [
            'admin.users.index' => ['admin/users/create', 'Tambah pengguna'],
            'admin.facilities.index' => ['admin/facilities/create', 'Tambah fasilitas'],
            'admin.categories.index' => ['admin/categories/create', 'Tambah kategori'],
        ];
    }

    public function test_the_action_button_is_inside_the_card_and_not_the_page_header(): void
    {
        $admin = User::factory()->admin()->create();

        foreach ($this->pages() as $route => [$path, $label]) {
            $html = $this->actingAs($admin)->get(route($route))->assertOk()->getContent();

            $this->assertStringContainsString($label, $html, "$route is missing its action button.");

            $cardStart = strpos($html, 'admin-card-heading');
            $button = strpos($html, $path);
            $headerEnd = strpos($html, 'page-header__actions');

            $this->assertNotFalse($button, "$route does not link to $path.");

            // The button sits after the card heading starts ...
            $this->assertGreaterThan($cardStart, $button, "$route button must be inside the table card.");

            // ... and is not within the page header block. The header actions
            // div is empty when the page places its action inside the card.
            $headerBlock = substr($html, $headerEnd, strpos($html, '</header>', $headerEnd) - $headerEnd);
            $this->assertStringNotContainsString($path, $headerBlock, "$route button must not sit in the page header.");
        }
    }

    public function test_the_pages_no_longer_declare_a_header_actions_section(): void
    {
        foreach (['admin/users/index', 'admin/facilities/index', 'admin/categories/index'] as $view) {
            $contents = file_get_contents(resource_path("views/$view.blade.php"));

            $this->assertStringNotContainsString(
                "@section('header-actions')",
                $contents,
                "$view should keep its action inside the card, not the page header."
            );
        }
    }
}
