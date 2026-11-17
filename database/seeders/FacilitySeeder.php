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
        'Meja Siswa',
        'Kursi Siswa',
        'Meja Guru',
        'Kursi Guru',
        'Papan Tulis',
        'Proyektor',
        'Komputer',
        'Laptop',
        'Printer',
        'Kipas Angin',
        'AC',
        'Lampu Ruangan',
        'Pintu Kelas',
        'Jendela Kelas',
        'Kaca Jendela',
        'Lemari Kelas',
        'Rak Buku',
        'Buku Perpustakaan',
        'Komputer Laboratorium',
        'Mikroskop',
        'Alat Peraga Pendidikan',
        'Alat Olahraga',
        'Bola Sepak',
        'Bola Basket',
        'Tiang Bendera',
        'Pagar Sekolah',
        'Gerbang Sekolah',
        'Keran Air',
        'Wastafel',
        'Toilet',
        'Pompa Air',
        'Atap Sekolah'
    ];
    public function run(): void
    {
        foreach (self::FACILITIES as $name) {
            Facility::query()->firstOrCreate(['name' => $name]);
        }
    }
}
