<?php

namespace Database\Seeders;

use App\Models\Facility;
use Illuminate\Database\Seeder;

/**
 * Facilities commonly found in an Indonesian high school.
 */
class FacilitySeeder extends Seeder
{
    /** @var list<string> */
    public const FACILITIES = [
        'Ruang Kelas X-1',
        'Ruang Kelas X-2',
        'Ruang Kelas XI-1',
        'Ruang Kelas XI-2',
        'Ruang Kelas XII-1',
        'Laboratorium Komputer',
        'Laboratorium IPA',
        'Perpustakaan',
        'Ruang Guru',
        'Ruang Kepala Sekolah',
        'Ruang Tata Usaha',
        'Ruang Bimbingan Konseling',
        'Aula Serbaguna',
        'Kantin Sekolah',
        'Toilet Siswa',
        'Toilet Guru',
        'Lapangan Basket',
        'Lapangan Upacara',
        'Masjid Sekolah',
        'Pos Keamanan',
        'Gudang',
        'Tempat Parkir',
    ];

    public function run(): void
    {
        foreach (self::FACILITIES as $name) {
            Facility::query()->firstOrCreate(['name' => $name]);
        }
    }
}
