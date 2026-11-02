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
                'nis' => '1000000001',
                'classroom' => 'XI-1',
                'role' => 'siswa',
            ],
        ];

        foreach ($accounts as $account) {
            // firstOrCreate keeps the seeder safe to re-run.
            User::query()->firstOrCreate(
                ['email' => $account['email']],
                [
                    'name' => $account['name'],
                    'nis' => $account['nis'] ?? null,
                    'classroom' => $account['classroom'] ?? null,
                    'role' => $account['role'],
                    'password' => Hash::make(self::PASSWORD),
                    'email_verified_at' => now(),
                ]
            );
        }

        // A handful of additional students so reports and discussions look real.
        $students = [
            ['name' => 'Anisa Putri', 'classroom' => 'X-1'],
            ['name' => 'Rizky Pratama', 'classroom' => 'X-2'],
            ['name' => 'Dewi Lestari', 'classroom' => 'XI-1'],
            ['name' => 'Fajar Nugroho', 'classroom' => 'XI-2'],
            ['name' => 'Siti Nurhaliza', 'classroom' => 'XII-1'],
            ['name' => 'Agus Setiawan', 'classroom' => 'XII-2'],
            ['name' => 'Maya Anggraini', 'classroom' => 'X-1'],
            ['name' => 'Dimas Ramadhan', 'classroom' => 'XI-1'],
        ];

        foreach ($students as $index => $student) {
            User::query()->firstOrCreate(
                ['email' => 'siswa'.($index + 1).'@benerin.test'],
                [
                    'name' => $student['name'],
                    // Seeded students get a stable NIS derived from their index.
                    'nis' => '10000000'.str_pad((string) ($index + 2), 2, '0', STR_PAD_LEFT),
                    'classroom' => $student['classroom'],
                    'role' => 'siswa',
                    'password' => Hash::make(self::PASSWORD),
                    'email_verified_at' => now(),
                ]
            );
        }
    }
}
