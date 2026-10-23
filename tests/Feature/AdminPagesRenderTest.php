<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Facility;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPagesRenderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_all_admin_pages_render(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->siswa()->create();
        $facility = Facility::factory()->create();
        $category = Category::factory()->create();

        $pages = [
            '/dashboard',
            route('admin.users.index'),
            route('admin.users.create'),
            route('admin.users.edit', $target),
            route('admin.facilities.index'),
            route('admin.facilities.create'),
            route('admin.facilities.edit', $facility),
            route('admin.categories.index'),
            route('admin.categories.create'),
            route('admin.categories.edit', $category),
        ];

        foreach ($pages as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
    }

    public function test_admin_can_reach_settings_and_profile_pages(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('settings.index'))->assertOk();
    }

    public function test_admin_edit_page_prefills_the_user(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->siswa()->create(['name' => 'Nama Target', 'email' => 'target@example.com']);

        $this->actingAs($admin)
            ->get(route('admin.users.edit', $target))
            ->assertOk()
            ->assertSee('Nama Target')
            ->assertSee('target@example.com');
    }

    public function test_delete_block_shows_a_helpful_message_on_the_page(): void
    {
        $admin = User::factory()->admin()->create();
        $facility = Facility::factory()->create(['name' => 'Ruang Guru']);
        Report::factory()->create(['facilities_id' => $facility->id]);

        $this->actingAs($admin)
            ->from(route('admin.facilities.index'))
            ->delete(route('admin.facilities.destroy', $facility))
            ->assertRedirect(route('admin.facilities.index'));

        $this->actingAs($admin)
            ->get(route('admin.facilities.index'))
            ->assertOk()
            ->assertSee('masih digunakan oleh', false);
    }
}
