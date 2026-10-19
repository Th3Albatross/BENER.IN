<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Facility;
use App\Models\Report;
use App\Models\User;
use Database\Factories\FacilityFactory;
use Database\Factories\UserFactory;
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

        //User::factory()->create([
        //    'name' => 'Test User',
        //    'email' => 'test@example.com',
        //]);
        $admin = User::create([
            'name' => 'admin',
            'email' => 'admin@example.com',
            'role' => 'admin',
            'password' => Hash::make('maybeimamazed'),
            'remember_token' => Str::random(10)
        ]);

        $admin = User::create([
            'name' => 'Fajar',
            'email' => 'user@user.com',
            'role' => 'user',
            'password' => Hash::make('ifallapart'),
            'remember_token' => Str::random(10)
        ]);

        //$users = User::factory(10);
        $categories = Category::factory(10);
        $facilities = Facility::factory(10);

        $categories->create();
        $facilities->create();

        /*Report::factory(72)
            ->recycle($admin)
            ->recycle($categories)
            ->recycle($facilities)
            ->create();*/
    }
}
