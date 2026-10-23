<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Facility;
use App\Models\Report;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // User::factory()->create([
        //    'name' => 'Test User',
        //    'email' => 'test@example.com',
        // ]);
        /*$admin = User::create([
            'name' => 'admin',
            'email' => 'admin@example.com',
            'role' => 'admin',
            'password' => Hash::make('maybeimamazed'),
            'remember_token' => Str::random(10)
        ]);

        $user = User::create([
            'name' => 'Fajar',
            'email' => 'user@user.com',
            'role' => 'siswa',
            'password' => Hash::make('ifallapart'),
            'remember_token' => Str::random(10)
        ]);

        //$users = User::factory(10);
        $categories = Category::factory(10);
        $facilities = Facility::factory(10);

        $categories->create();
        $facilities->create();*/

        $petugas = User::create([
            'name' => 'Void',
            'email' => 'petugas@pet.ugas',
            'role' => 'petugas',
            'password' => Hash::make('ifallapart'),
            'remember_token' => Str::random(10),
        ]);

        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@be.ner',
            'role' => User::ADMIN_ROLE,
            'password' => Hash::make('ifallapart'),
            'remember_token' => Str::random(10),
        ]);

        /*Report::factory(72)
            ->recycle($admin)
            ->recycle($categories)
            ->recycle($facilities)
            ->create();*/
    }
}
