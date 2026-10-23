<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Facility;
use App\Models\Report;
use App\Models\Response;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    /* ----------------------------- users ----------------------------- */

    public function test_admin_can_create_a_petugas_account(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.users.store'), [
                'name' => 'Petugas Baru',
                'email' => 'petugas.baru@example.com',
                'role' => 'petugas',
                'password' => 'rahasia123',
                'password_confirmation' => 'rahasia123',
            ])
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('status', 'user-created');

        $user = User::where('email', 'petugas.baru@example.com')->firstOrFail();
        $this->assertSame('petugas', $user->role);
        // The hashed cast must have hashed the password on save.
        $this->assertNotSame('rahasia123', $user->password);
    }

    public function test_admin_cannot_create_another_admin(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.users.store'), [
                'name' => 'Admin Palsu',
                'email' => 'admin.palsu@example.com',
                'role' => 'admin',
                'password' => 'rahasia123',
                'password_confirmation' => 'rahasia123',
            ])
            ->assertSessionHasErrors('role');

        $this->assertDatabaseMissing('users', ['email' => 'admin.palsu@example.com']);
    }

    public function test_creating_a_user_requires_a_unique_email_and_matching_password(): void
    {
        $existing = User::factory()->siswa()->create();

        $this->actingAs($this->admin())
            ->post(route('admin.users.store'), [
                'name' => 'Duplikat',
                'email' => $existing->email,
                'role' => 'siswa',
                'password' => 'rahasia123',
                'password_confirmation' => 'beda12345',
            ])
            ->assertSessionHasErrors(['email', 'password']);
    }

    public function test_admin_can_update_a_users_name_email_and_role(): void
    {
        $user = User::factory()->siswa()->create();

        $this->actingAs($this->admin())
            ->put(route('admin.users.update', $user), [
                'name' => 'Nama Diubah',
                'email' => 'diubah@example.com',
                'role' => 'petugas',
            ])
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('status', 'user-updated');

        $user->refresh();
        $this->assertSame('Nama Diubah', $user->name);
        $this->assertSame('diubah@example.com', $user->email);
        $this->assertSame('petugas', $user->role);
    }

    public function test_updating_without_a_password_keeps_the_existing_password(): void
    {
        $user = User::factory()->siswa()->create(['password' => 'password-asli']);
        $originalHash = $user->password;

        $this->actingAs($this->admin())
            ->put(route('admin.users.update', $user), [
                'name' => $user->name,
                'email' => $user->email,
                'role' => 'siswa',
                'password' => '',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame($originalHash, $user->refresh()->password);
    }

    public function test_admin_cannot_edit_or_delete_themselves(): void
    {
        $admin = $this->admin();
        $originalName = $admin->name;

        $this->actingAs($admin)->get(route('admin.users.edit', $admin))->assertForbidden();
        $this->actingAs($admin)->put(route('admin.users.update', $admin), [
            'name' => 'Hacked',
            'email' => $admin->email,
            'role' => 'siswa',
        ])->assertForbidden();
        $this->actingAs($admin)->delete(route('admin.users.destroy', $admin))->assertForbidden();

        $this->assertSame($originalName, $admin->refresh()->name);
        $this->assertTrue($admin->isAdmin());
    }

    public function test_admin_cannot_edit_or_delete_another_admin(): void
    {
        $admin = $this->admin();
        $other = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('admin.users.edit', $other))->assertForbidden();
        $this->actingAs($admin)->delete(route('admin.users.destroy', $other))->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $other->id]);
    }

    public function test_admin_can_delete_a_user_with_no_activity(): void
    {
        $user = User::factory()->siswa()->create();

        $this->actingAs($this->admin())
            ->delete(route('admin.users.destroy', $user))
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('status', 'user-deleted');

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_deleting_a_user_with_reports_is_blocked(): void
    {
        $user = User::factory()->siswa()->create();
        Report::factory()->create(['users_id' => $user->id]);

        $this->actingAs($this->admin())
            ->delete(route('admin.users.destroy', $user))
            ->assertSessionHasErrors('user');

        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    public function test_deleting_a_user_with_only_discussion_messages_is_blocked(): void
    {
        $author = User::factory()->siswa()->create();
        $report = Report::factory()->create(['users_id' => $author->id]);
        $commenter = User::factory()->petugas()->create();

        Response::create([
            'users_id' => $commenter->id,
            'reports_id' => $report->id,
            'message' => 'Pesan diskusi.',
        ]);

        $this->actingAs($this->admin())
            ->delete(route('admin.users.destroy', $commenter))
            ->assertSessionHasErrors('user');

        $this->assertDatabaseHas('users', ['id' => $commenter->id]);
    }

    /* --------------------------- facilities --------------------------- */

    public function test_admin_can_create_and_rename_a_facility(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.facilities.store'), ['name' => 'Laboratorium Komputer'])
            ->assertRedirect(route('admin.facilities.index'))
            ->assertSessionHas('status', 'facility-created');

        $facility = Facility::where('name', 'Laboratorium Komputer')->firstOrFail();

        $this->actingAs($admin)
            ->put(route('admin.facilities.update', $facility), ['name' => 'Lab Komputer'])
            ->assertSessionHas('status', 'facility-updated');

        $this->assertSame('Lab Komputer', $facility->refresh()->name);
    }

    public function test_facility_name_is_required(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.facilities.store'), ['name' => ''])
            ->assertSessionHasErrors('name');
    }

    public function test_admin_can_delete_an_unused_facility(): void
    {
        $facility = Facility::factory()->create();

        $this->actingAs($this->admin())
            ->delete(route('admin.facilities.destroy', $facility))
            ->assertSessionHas('status', 'facility-deleted');

        $this->assertDatabaseMissing('facilities', ['id' => $facility->id]);
    }

    public function test_deleting_a_facility_in_use_is_blocked(): void
    {
        $facility = Facility::factory()->create();
        Report::factory()->create(['facilities_id' => $facility->id]);

        $this->actingAs($this->admin())
            ->delete(route('admin.facilities.destroy', $facility))
            ->assertSessionHasErrors('facility');

        $this->assertDatabaseHas('facilities', ['id' => $facility->id]);
    }

    /* --------------------------- categories --------------------------- */

    public function test_admin_can_create_and_rename_a_category(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.categories.store'), ['name' => 'Listrik'])
            ->assertRedirect(route('admin.categories.index'))
            ->assertSessionHas('status', 'category-created');

        $category = Category::where('name', 'Listrik')->firstOrFail();

        $this->actingAs($admin)
            ->put(route('admin.categories.update', $category), ['name' => 'Kelistrikan'])
            ->assertSessionHas('status', 'category-updated');

        $this->assertSame('Kelistrikan', $category->refresh()->name);
    }

    public function test_admin_can_delete_an_unused_category(): void
    {
        $category = Category::factory()->create();

        $this->actingAs($this->admin())
            ->delete(route('admin.categories.destroy', $category))
            ->assertSessionHas('status', 'category-deleted');

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_deleting_a_category_in_use_is_blocked(): void
    {
        $category = Category::factory()->create();
        Report::factory()->create(['categories_id' => $category->id]);

        $this->actingAs($this->admin())
            ->delete(route('admin.categories.destroy', $category))
            ->assertSessionHasErrors('category');

        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }

    /* --------------------------- listing --------------------------- */

    public function test_management_pages_list_existing_records(): void
    {
        $facility = Facility::factory()->create(['name' => 'Aula Serbaguna']);
        $category = Category::factory()->create(['name' => 'Kebersihan']);
        $user = User::factory()->siswa()->create(['name' => 'Budi Santoso']);
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.facilities.index'))->assertOk()->assertSee('Aula Serbaguna');
        $this->actingAs($admin)->get(route('admin.categories.index'))->assertOk()->assertSee('Kebersihan');
        $this->actingAs($admin)->get(route('admin.users.index'))->assertOk()->assertSee('Budi Santoso');
    }

    public function test_user_list_can_be_filtered_by_role(): void
    {
        $admin = $this->admin();
        User::factory()->petugas()->create(['name' => 'Petugas Satu']);
        User::factory()->siswa()->create(['name' => 'Siswa Satu']);

        $this->actingAs($admin)
            ->get(route('admin.users.index', ['role' => 'petugas']))
            ->assertOk()
            ->assertSee('Petugas Satu')
            ->assertDontSee('Siswa Satu');

        // An unknown role is ignored rather than filtering everything out.
        $this->actingAs($admin)
            ->get(route('admin.users.index', ['role' => 'admin']))
            ->assertOk()
            ->assertSee('Siswa Satu');
    }

    public function test_the_student_count_matches_the_student_filter(): void
    {
        $admin = $this->admin();
        User::factory()->siswa()->create(['name' => 'Siswa Satu']);
        User::factory()->petugas()->create(['name' => 'Petugas Satu']);

        $counts = $this->actingAs($admin)->get('/dashboard')->assertOk()->viewData('_counts');

        $this->assertSame(1, $counts['students']);
        $this->assertSame(1, $counts['petugas']);
    }
}
