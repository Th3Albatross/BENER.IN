<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Facility;
use App\Models\Report;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seeds reports and their discussions using realistic Indonesian school
 * facility content. Reports are spread over the last few months so the
 * analytics trend chart has something meaningful to draw.
 *
 * The data is deterministic: re-running the seeder produces the same reports.
 */
class ReportSeeder extends Seeder
{
    /**
     * @var list<array{title: string, category: string, facility: string, severity: string, status: string, location: string, description: string}>
     */
    private const REPORTS = [
        [
            'title' => 'AC ruang kelas tidak dingin',
            'category' => 'Kelistrikan',
            'facility' => 'Ruang Kelas X-1',
            'severity' => 'Medium',
            'status' => 'Resolved',
            'location' => 'Ruang Kelas X-1, sayap timur lantai 1',
            'description' => 'AC sudah menyala tetapi tidak mengeluarkan udara dingin sejak awal minggu. Ruangan menjadi panas pada jam pelajaran siang sehingga mengganggu konsentrasi belajar.',
        ],
        [
            'title' => 'Lampu ruang kelas mati',
            'category' => 'Kelistrikan',
            'facility' => 'Ruang Kelas XI-2',
            'severity' => 'High',
            'status' => 'Resolved',
            'location' => 'Ruang Kelas XI-2, deretan lampu depan',
            'description' => 'Empat lampu di barisan depan mati total. Saat cuaca mendung ruangan terlalu gelap untuk membaca papan tulis.',
        ],
        [
            'title' => 'Kran air toilet bocor',
            'category' => 'Air dan Sanitasi',
            'facility' => 'Toilet Siswa',
            'severity' => 'Medium',
            'status' => 'Pending',
            'location' => 'Toilet siswa lantai 1, wastafel ketiga',
            'description' => 'Kran air terus menetes meskipun sudah ditutup rapat. Air tergenang di lantai dan membuat area sekitar licin.',
        ],
        [
            'title' => 'Toilet siswa tersumbat',
            'category' => 'Air dan Sanitasi',
            'facility' => 'Toilet Siswa',
            'severity' => 'Critical',
            'status' => 'Pending',
            'location' => 'Toilet siswa lantai 2',
            'description' => 'Saluran pembuangan tersumbat sehingga air meluap ke lantai. Toilet tidak dapat digunakan dan menimbulkan bau tidak sedap.',
        ],
        [
            'title' => 'Komputer laboratorium tidak menyala',
            'category' => 'Kerusakan Berat',
            'facility' => 'Laboratorium Komputer',
            'severity' => 'High',
            'status' => 'Resolved',
            'location' => 'Laboratorium Komputer, meja nomor 12',
            'description' => 'Komputer di meja nomor 12 tidak menyala sama sekali ketika tombol power ditekan. Kabel dan stopkontak sudah diperiksa namun tetap tidak ada respon.',
        ],
        [
            'title' => 'Meja belajar goyang',
            'category' => 'Furnitur',
            'facility' => 'Ruang Kelas X-2',
            'severity' => 'Low',
            'status' => 'Resolved',
            'location' => 'Ruang Kelas X-2, meja baris belakang',
            'description' => 'Salah satu meja siswa goyang karena baut penyangga hilang. Meja sulit digunakan untuk menulis.',
        ],
        [
            'title' => 'Kursi guru patah',
            'category' => 'Furnitur',
            'facility' => 'Ruang Guru',
            'severity' => 'Medium',
            'status' => 'Resolved',
            'location' => 'Ruang Guru, meja dekat jendela',
            'description' => 'Sandaran kursi guru patah pada bagian penyangga sehingga tidak bisa disandari.',
        ],
        [
            'title' => 'WiFi tidak dapat diakses',
            'category' => 'Jaringan Internet',
            'facility' => 'Perpustakaan',
            'severity' => 'Medium',
            'status' => 'Pending',
            'location' => 'Perpustakaan, area baca utama',
            'description' => 'Jaringan WiFi tidak muncul pada daftar jaringan di perangkat siswa sejak dua hari terakhir. Siswa tidak dapat mengakses materi digital.',
        ],
        [
            'title' => 'Proyektor ruang kelas rusak',
            'category' => 'Kerusakan Berat',
            'facility' => 'Ruang Kelas XII-1',
            'severity' => 'High',
            'status' => 'Pending',
            'location' => 'Ruang Kelas XII-1, langit-langit tengah',
            'description' => 'Proyektor menyala tetapi hanya menampilkan warna putih. Guru tidak dapat menampilkan materi presentasi.',
        ],
        [
            'title' => 'Sampah menumpuk di kantin',
            'category' => 'Kebersihan',
            'facility' => 'Kantin Sekolah',
            'severity' => 'Medium',
            'status' => 'Resolved',
            'location' => 'Kantin sekolah, dekat tempat cuci tangan',
            'description' => 'Tempat sampah penuh dan tidak diangkut selama dua hari sehingga sampah berserakan di sekitar area kantin.',
        ],
        [
            'title' => 'Papan tulis kotor dan sulit dihapus',
            'category' => 'Kebersihan',
            'facility' => 'Ruang Kelas XI-1',
            'severity' => 'Low',
            'status' => 'Rejected',
            'location' => 'Ruang Kelas XI-1, papan tulis depan',
            'description' => 'Bekas spidol sulit dihapus sehingga papan tulis terlihat kotor. Kemungkinan perlu dibersihkan dengan cairan khusus.',
        ],
        [
            'title' => 'Pintu kelas tidak dapat dikunci',
            'category' => 'Keamanan',
            'facility' => 'Ruang Kelas X-1',
            'severity' => 'High',
            'status' => 'Resolved',
            'location' => 'Ruang Kelas X-1, pintu utama',
            'description' => 'Gagang pintu longgar dan kunci tidak dapat diputar sehingga ruangan tidak bisa dikunci saat jam istirahat.',
        ],
        [
            'title' => 'Pagar lapangan basket rusak',
            'category' => 'Keamanan',
            'facility' => 'Lapangan Basket',
            'severity' => 'High',
            'status' => 'Pending',
            'location' => 'Lapangan basket, sisi barat',
            'description' => 'Sebagian pagar pembatas bengkok dan berlubang sehingga bola sering keluar area lapangan.',
        ],
        [
            'title' => 'Keran wastafel masjid tidak mengalir',
            'category' => 'Air dan Sanitasi',
            'facility' => 'Masjid Sekolah',
            'severity' => 'Medium',
            'status' => 'Resolved',
            'location' => 'Masjid sekolah, area wudhu',
            'description' => 'Dua keran di area wudhu tidak mengeluarkan air sama sekali sejak pagi.',
        ],
        [
            'title' => 'Lampu koridor berkedip',
            'category' => 'Kelistrikan',
            'facility' => 'Ruang Tata Usaha',
            'severity' => 'Medium',
            'status' => 'Resolved',
            'location' => 'Koridor depan ruang tata usaha',
            'description' => 'Lampu koridor berkedip terus menerus dan menimbulkan suara berdengung.',
        ],
        [
            'title' => 'Atap bocor saat hujan',
            'category' => 'Kerusakan Berat',
            'facility' => 'Laboratorium IPA',
            'severity' => 'Critical',
            'status' => 'Pending',
            'location' => 'Laboratorium IPA, sisi timur dekat lemari bahan',
            'description' => 'Air menetes dari langit-langit saat hujan deras dan mengenai meja praktikum. Perlu penanganan segera agar alat laboratorium tidak rusak.',
        ],
        [
            'title' => 'Buku perpustakaan rusak',
            'category' => 'Furnitur',
            'facility' => 'Perpustakaan',
            'severity' => 'Low',
            'status' => 'Rejected',
            'location' => 'Perpustakaan, rak nomor 3',
            'description' => 'Beberapa buku referensi lembab dan halamannya sobek sehingga sulit dibaca.',
        ],
        [
            'title' => 'Speaker aula tidak berfungsi',
            'category' => 'Kelistrikan',
            'facility' => 'Aula Serbaguna',
            'severity' => 'Medium',
            'status' => 'Resolved',
            'location' => 'Aula serbaguna, sudut kanan panggung',
            'description' => 'Salah satu speaker tidak mengeluarkan suara saat kegiatan sekolah berlangsung.',
        ],
    ];

    public function run(): void
    {
        $students = User::query()
            ->where('role', 'siswa')
            ->orderBy('id')
            ->get();

        $petugas = User::query()
            ->where('role', 'petugas')
            ->orderBy('id')
            ->first();

        if ($students->isEmpty() || $petugas === null) {
            $this->command?->warn('ReportSeeder dilewati: jalankan UserSeeder terlebih dahulu.');

            return;
        }

        $categories = Category::query()->pluck('id', 'name');
        $facilities = Facility::query()->pluck('id', 'name');
        $now = CarbonImmutable::now();

        foreach (self::REPORTS as $index => $data) {
            // Spread reports backwards over roughly the last 100 days.
            $createdAt = $now->subDays(100 - ($index * 5))->setTime(8 + ($index % 8), 15 + ($index % 40));

            // Keyed on the title so re-running the seeder does not duplicate rows.
            $report = Report::query()->firstOrCreate(
                ['title' => $data['title']],
                [
                    'identifier' => (string) Str::uuid7(),
                    'status' => $data['status'],
                    'severity' => $data['severity'],
                    'categories_id' => $categories[$data['category']] ?? null,
                    'users_id' => $students[$index % $students->count()]->id,
                    'facilities_id' => $facilities[$data['facility']] ?? $facilities->first(),
                    'title' => $data['title'],
                    'location_description' => $data['location'],
                    'description' => $data['description'],
                    'incident_date' => $createdAt->toDateString(),
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]
            );

            $this->seedDiscussion($report, $petugas, $createdAt);
        }
    }

    /** Give open and resolved reports a short conversation with petugas. */
    private function seedDiscussion(Report $report, User $petugas, CarbonImmutable $createdAt): void
    {
        if ($report->responses()->exists()) {
            return;
        }

        $studentMessage = 'Apakah laporan ini sudah ditindaklanjuti? Terima kasih.';

        if ($report->status === 'Pending') {
            $petugasMessage = 'Terima kasih atas laporannya. Kami sedang mengecek kondisi di lokasi.';
        } elseif ($report->status === 'Resolved') {
            $petugasMessage = 'Perbaikan sudah selesai dikerjakan. Silakan dicek kembali, terima kasih.';
        } else {
            $petugasMessage = 'Laporan ini kami tolak karena kerusakan tidak ditemukan saat pengecekan.';
        }

        $messages = [
            ['users_id' => $report->users_id, 'message' => $studentMessage, 'at' => $createdAt->addHours(2)],
            ['users_id' => $petugas->id, 'message' => $petugasMessage, 'at' => $createdAt->addHours(5)],
        ];

        foreach ($messages as $message) {
            $response = $report->responses()->create([
                'users_id' => $message['users_id'],
                'message' => $message['message'],
            ]);

            // created_at is not fillable, so the historical timestamp is set directly.
            $response->forceFill([
                'created_at' => $message['at'],
                'updated_at' => $message['at'],
            ])->saveQuietly();
        }
    }
}
