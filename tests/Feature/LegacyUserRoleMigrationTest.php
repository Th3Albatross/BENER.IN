<?php

namespace Tests\Feature;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * The student role was originally stored as "user" and renamed to "siswa" by
 * editing the base migration. Databases created before that rename still hold
 * role = "user", which `where('role', 'siswa')` can never match. This covers the
 * data migration that rewrites those rows.
 */
class LegacyUserRoleMigrationTest extends TestCase
{
    use RefreshDatabase;

    private function legacyMigration(): Migration
    {
        return require database_path('migrations/2026_10_23_000001_migrate_legacy_user_role_to_siswa.php');
    }

    /** Rebuild the pre-rename schema so the legacy value is storable again. */
    private function restoreLegacyState(): void
    {
        Schema::table('users', function ($table): void {
            $table->enum('role', ['user', 'petugas', 'admin'])->default('user')->change();
        });

        $password = str_repeat('x', 60);

        DB::table('users')->insert([
            ['name' => 'Legacy A', 'email' => 'legacy.a@example.com', 'password' => $password, 'role' => 'user'],
            ['name' => 'Legacy B', 'email' => 'legacy.b@example.com', 'password' => $password, 'role' => 'user'],
            ['name' => 'Petugas', 'email' => 'petugas@example.com', 'password' => $password, 'role' => 'petugas'],
            ['name' => 'Boss', 'email' => 'boss@example.com', 'password' => $password, 'role' => 'admin'],
        ]);
    }

    public function test_it_rewrites_legacy_user_rows_to_siswa(): void
    {
        $this->restoreLegacyState();

        $this->assertSame(2, DB::table('users')->where('role', 'user')->count());

        $this->legacyMigration()->up();

        $this->assertSame(0, DB::table('users')->where('role', 'user')->count());
        $this->assertSame(2, DB::table('users')->where('role', 'siswa')->count());
    }

    public function test_it_leaves_petugas_and_admin_untouched(): void
    {
        $this->restoreLegacyState();

        $this->legacyMigration()->up();

        $this->assertSame(1, DB::table('users')->where('role', 'petugas')->count());
        $this->assertSame(1, DB::table('users')->where('role', 'admin')->count());
    }

    public function test_the_final_enum_rejects_the_legacy_value(): void
    {
        $this->restoreLegacyState();

        $this->legacyMigration()->up();

        $this->expectException(QueryException::class);

        DB::table('users')->insert([
            'name' => 'Stray',
            'email' => 'stray@example.com',
            'password' => str_repeat('x', 60),
            'role' => 'user',
        ]);
    }

    public function test_the_migration_is_safe_to_run_when_no_legacy_rows_exist(): void
    {
        $this->assertSame(0, DB::table('users')->where('role', 'user')->count());

        $this->legacyMigration()->up();

        // Running it again must not error or alter anything.
        $this->legacyMigration()->up();

        $this->assertSame(0, DB::table('users')->where('role', 'user')->count());
    }
}
