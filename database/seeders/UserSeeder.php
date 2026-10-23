<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeds the three account types used by the application.
 *
 * The passwords here are development defaults so the app is usable straight
 * after `migrate --seed`. Change them before deploying anywhere real.
 */
class UserSeeder extends Seeder
{
    /** Shared development password for every seeded account. */
    private const PASSWORD = 'password';

    public function run(): void
    {
        $accounts = [
            [
                'name' => 'Administrator',
                'email' => 'admin@benerin.test',
                'role' => User::ADMIN_ROLE,
            ],
            [
                'name' => 'Petugas Sarana',
                'email' => 'petugas@benerin.test',
                'role' => 'petugas',
            ],
            [
                'name' => 'Budi Santoso',
                'email' => 'siswa@benerin.test',
                'role' => 'siswa',
            ],
        ];

        foreach ($accounts as $account) {
            // firstOrCreate keeps the seeder safe to re-run.
            User::query()->firstOrCreate(
                ['email' => $account['email']],
                [
                    'name' => $account['name'],
                    'role' => $account['role'],
                    'password' => Hash::make(self::PASSWORD),
                    'email_verified_at' => now(),
                ]
            );
        }

        // A handful of additional students so reports and discussions look real.
        $students = [
            'Anisa Putri',
            'Rizky Pratama',
            'Dewi Lestari',
            'Fajar Nugroho',
            'Siti Nurhaliza',
            'Agus Setiawan',
            'Maya Anggraini',
            'Dimas Ramadhan',
        ];

        foreach ($students as $index => $name) {
            User::query()->firstOrCreate(
                ['email' => 'siswa'.($index + 1).'@benerin.test'],
                [
                    'name' => $name,
                    'role' => 'siswa',
                    'password' => Hash::make(self::PASSWORD),
                    'email_verified_at' => now(),
                ]
            );
        }
    }
}
