# BENER.IN

**Sistem Pelaporan Fasilitas Sekolah** — aplikasi web untuk melaporkan, menindaklanjuti, dan mengelola kerusakan fasilitas di lingkungan sekolah menengah.

---

## Tentang Proyek

BENER.IN adalah aplikasi pelaporan fasilitas berbasis web yang dirancang untuk sekolah menengah (SMA/SMK sederajat). Siswa dapat melaporkan kerusakan atau masalah fasilitas sekolah — seperti lampu kelas yang mati, kran air yang bocor, atau AC yang tidak dingin — lalu petugas (staf sarana) menindaklanjuti laporan tersebut hingga selesai atau ditolak.

Sebelumnya, pelaporan dilakukan secara lisan atau lewat grup pesan, sehingga laporan mudah terlewat, tidak terdokumentasi, dan sulit dipantau. BENER.IN menggantikannya dengan satu alur yang tercatat: laporan masuk, memiliki status yang jelas, dapat didiskusikan, dan hasilnya dapat dilihat kembali sebagai statistik.

### Tujuan

1. **Mempermudah siswa** melaporkan kerusakan fasilitas beserta foto bukti.
2. **Membantu petugas** memprioritaskan dan menindaklanjuti laporan secara terukur.
3. **Memberi admin** kontrol penuh atas data induk (pengguna, fasilitas, kategori) tanpa harus menyentuh laporan secara langsung.
4. **Menyediakan data** berupa statistik dan analitik untuk evaluasi kondisi fasilitas sekolah.

---

## Fitur Utama

### Siswa
- Membuat laporan kerusakan (judul, fasilitas, kategori, tingkat keparahan, lokasi, tanggal kejadian, deskripsi, dan maksimal 3 foto bukti).
- Mengedit laporan yang masih berstatus **Menunggu**, termasuk mengganti atau menghapus foto.
- Melihat detail laporan beserta status dan riwayat penanganannya.
- Berdiskusi dengan petugas pada halaman diskusi tiap laporan.
- Dasbor pribadi berisi ringkasan status laporan sendiri.
- Mengekspor detail laporan menjadi PDF (melalui dialog cetak peramban).

### Petugas (Staf Sarana)
- Melihat dan menyaring seluruh laporan: pencarian, filter pengguna, status, keparahan, fasilitas, kategori, serta opsi pengurutan.
- Mengubah status laporan menjadi **Selesai** atau **Ditolak** (keputusan final dan hanya satu arah).
- Berdiskusi dengan pelapor pada setiap laporan.
- **Analitik**: statistik laporan berdasarkan periode (7 hari, 30 hari, 90 hari, semua, atau rentang kustom) dalam bentuk grafik dan tabel, serta dapat diekspor ke PDF.

### Admin
Admin **tidak menangani laporan secara langsung**. Fokus admin adalah manajemen data induk:
- **Pengguna** — menambah, mengubah, dan menghapus akun siswa serta petugas; melihat jumlah laporan dan pesan tiap akun; memfilter berdasarkan peran dan mencari berdasarkan nama/NIS/kelas/email.
- **Fasilitas** — mengelola daftar fasilitas yang dapat dilaporkan, beserta jumlah laporan dan indikator pemakaian.
- **Kategori** — mengelola kategori laporan beserta indikator kategori yang belum pernah dipakai.
- **Dasbor admin** — ringkasan jumlah data induk, grafik komposisi peran, siswa per kelas, fasilitas dan kategori yang paling sering dilaporkan, serta panel **Kelengkapan Data** yang menandai data yang belum lengkap (mis. siswa tanpa NIS atau kelas, fasilitas yang belum pernah dilaporkan).

### Umum
- Tema tampilan **Terang / Redup / Gelap** yang dapat diganti dan disimpan di peramban (tanpa membebani basis data).
- Pengaturan akun: foto profil, email, dan kata sandi.
- Antarmuka responsif (mobile-friendly) dengan sidebar yang menyesuaikan diri pada layar kecil.
- Seluruh antarmuka menggunakan Bahasa Indonesia.

---

## Peran Pengguna

| Peran | Login dengan | Hak akses |
| --- | --- | --- |
| **Siswa** | NIS | Membuat & mengelola laporan sendiri, berdiskusi, mengatur profil |
| **Petugas** | Email | Melihat & memproses semua laporan, berdiskusi, analitik, mengatur profil |
| **Admin** | Email | Mengelola pengguna, fasilitas, dan kategori (data induk) |

Sistem ini bersifat **tertutup**: tidak ada pendaftaran mandiri. Akun siswa dan petugas dibuat oleh admin.

---

## Alur Status Laporan

```
Pending (Menunggu) ──► Resolved (Selesai)
                   └─► Rejected (Ditolak)
```

Status awal setiap laporan adalah **Pending**. Petugas memutuskan sekali saja; keputusan bersifat final dan tidak dapat dibatalkan. Selama status masih Pending, siswa masih dapat mengedit laporannya.

**Tingkat keparahan** laporan: `Low` (Rendah), `Medium` (Sedang), `High` (Tinggi), `Critical` (Kritis).

---

## Teknologi

### Backend
- **PHP 8.5**
- **Laravel 13**
- **MariaDB** (pengembangan lokal) · **SQLite** (produksi, disimpan pada volume persisten)
- **Laravel Tinker** untuk REPL
- Ekstensi **ext-sodium** (kriptografi untuk tautan foto)

### Frontend
- **Blade** (template engine Laravel)
- **CSS murni** yang ditulis manual, satu berkas per halaman (`resources/css/pages/*.css`)
- **Vite 8** + `laravel-vite-plugin` untuk bundling aset
- **Chart.js 4** untuk grafik pada dasbor dan halaman analitik
- **Geist** sebagai tipografi antarmuka (dimuat melalui Google Fonts)

### Pengujian & Kualitas Kode
- **Pest 5** (+ `pest-plugin-laravel`) — 220 pengujian
- **Laravel Pint** untuk konsistensi gaya kode
- **FakerPHP** untuk data palsu pada factory
- **Laravel Pail** untuk memantau log

### Infrastruktur
- **Docker** dengan image `serversideup/php:8.5-frankenphp`
- **Fly.io** sebagai platform deployment (volume persisten untuk basis data dan berkas unggahan)
- **GitHub Actions** untuk deployment otomatis (`.github/workflows/fly-deploy.yml`)

---

## Kebutuhan Sistem

- PHP **8.5** atau lebih baru dengan ekstensi: `sodium`, `pdo`, `mbstring`, `gd`
- Composer 2
- Node.js 18+ dan NPM
- MariaDB / MySQL (untuk pengembangan lokal)

---

## Instalasi (Lingkungan Pengembangan)

```bash
# 1. Klon repositori
git clone <url-repositori> sistem_pengaduan
cd sistem_pengaduan

# 2. Pasang dependensi dan siapkan environment
composer run setup
```

Perintah `composer run setup` akan memasang dependensi PHP, membuat berkas `.env` dari `.env.example`, membuat `APP_KEY`, menjalankan migrasi, memasang paket NPM, dan membangun aset.

Selanjutnya, sesuaikan koneksi basis data pada `.env`:

```env
DB_CONNECTION=mariadb
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sistem_pengaduan
DB_USERNAME=root
DB_PASSWORD=
```

Lalu isi basis data dengan data contoh dan jalankan server pengembangan:

```bash
php artisan migrate --seed     # migrasi + data contoh
composer run dev               # server + Vite (hot reload)
```

Aplikasi dapat diakses di `http://localhost:8000`.

> **Catatan:** `SCRAMBLR_KEY` **wajib** diatur agar tautan foto dapat dibuka lintas permintaan. Buat kunci dengan `php -r "echo base64_encode(random_bytes(32));"`, lalu isikan ke `.env`. Tanpa kunci ini, setiap proses akan membuat kunci acak sendiri sehingga foto tidak akan tampil.

---

## Akun Demo (Hasil Seeder)

| Peran | Kredensial | Kata sandi |
| --- | --- | --- |
| Admin | `admin@benerin.test` | `password` |
| Petugas | `petugas@benerin.test` | `password` |
| Siswa | NIS `1000000001` | `password` |

Seeder juga menambahkan sejumlah siswa beserta laporan contoh agar dasbor dan halaman analitik langsung terisi data. Kata sandi di atas **hanya untuk pengembangan** dan wajib diganti sebelum dipakai di lingkungan nyata.

---

## Pengujian

```bash
php artisan test                        # seluruh pengujian
php artisan test --filter=AdminAccessTest   # pengujian tertentu
./vendor/bin/pint                       # pemeriksa & perapi gaya kode
```

Pengujian berjalan di atas SQLite in-memory dan mencakup, antara lain: kontrol akses per peran, validasi formulir laporan, keamanan unggahan berkas, konsistensi tema, tampilan mobile, serta integritas data hasil seeder.

---

## Deployment (Fly.io)

Proyek ini disiapkan untuk deployment ke Fly.io:

```bash
fly deploy
```

Konfigurasi utama:

| Berkas | Fungsi |
| --- | --- |
| `Dockerfile` | Membangun aset dengan Vite, memasang dependensi produksi, menyiapkan direktori |
| `.dockerignore` | Mengecualikan berkas pengembangan (termasuk `public/hot` dan data runtime) dari image |
| `fly.toml` | Konfigurasi aplikasi: region, VM, _health check_, dan variabel lingkungan |
| `docker/init-storage.sh` | Menautkan `storage/` ke volume persisten `/data` saat kontainer pertama kali dijalankan |
| `docker/init-sqlite.sh` | Membuat basis data SQLite, menjalankan migrasi, dan mengisi data contoh saat boot |

**Rahasia (secrets) yang diperlukan:**

```bash
fly secrets set APP_KEY=base64:<kunci-anda>
fly secrets set SCRAMBLR_KEY=<kunci-base64-32-byte>
```

**Catatan operasional:**
- `APP_DEBUG` harus `false` dan `APP_ENV` harus `production` pada lingkungan nyata.
- Machine pada Fly.io diatur _auto-stop_. Saat idle, machine dimatikan sehingga kunjungan pertama berikutnya akan lebih lambat (proses boot). Untuk keperluan demo, akses situs beberapa saat lebih awal agar machine sudah menyala.
- Aset frontend harus dibangun (`npm run build`) sebelum dideploy; berjenis `public/build` tidak disimpan di repositori.

---

## Struktur Proyek

```
app/
├── Http/
│   ├── Controllers/           # Dashboard, Report, Responses, Settings, Analytics, Blob
│   │   └── Admin/             # UserController, FacilityController, CategoryController
│   └── Middleware/            # EnsureUserHasRole (pembatasan akses per peran)
├── Models/                    # User, Report, Facility, Category, Response
└── Services/
    ├── AnalyticsReportService.php   # Perhitungan statistik laporan
    ├── ScramblrService.php          # Token tautan foto (terenkripsi & berbatas waktu)
    └── SidebarNavigation.php        # Penentuan menu aktif di sidebar

database/
├── migrations/                # Skema basis data
├── factories/                 # Data palsu untuk pengujian
└── seeders/                   # Data contoh (pengguna, fasilitas, kategori, laporan)

resources/
├── css/
│   ├── theme.css              # Token warna untuk 3 mode tema
│   ├── layouts/app.css        # Kerangka & sidebar
│   └── pages/                 # Satu berkas CSS per halaman
├── js/                        # admin-dashboard, analytics, sidebar, theme
└── views/
    ├── layouts/               # Layout utama & sidebar
    ├── admin/                 # Halaman manajemen data induk
    ├── dashboard/             # Dasbor siswa, petugas, admin
    ├── report/                # Form laporan & halaman detail
    ├── discussion/            # Diskusi
    ├── analytics/ · settings/ · auth/ · components/
    └── ...

tests/Feature/                 # Pengujian fitur (Pest)
```

---

## Keamanan & Catatan Teknis

- **Kontrol akses berbasis peran** melalui middleware `role` di setiap rute. Admin tidak memiliki akses apa pun ke halaman laporan, diskusi, maupun analitik.
- **Berkas unggahan tidak dapat diakses langsung.** Foto disimpan di `storage/app/private` dan hanya disajikan melalui rute `/blob/{token}` yang memverifikasi token terenkripsi (berlaku 30 menit). Token tidak dapat ditebak dan tidak dapat dipalsukan.
- **Validasi menyeluruh di sisi server** untuk seluruh formulir: panjang teks, jenis dan ukuran berkas, keberadaan relasi, serta rentang tanggal.
- **Proteksi CSRF** pada semua formulir, pembatasan tipe dan ukuran berkat (`JPG/PNG/WEBP`, maksimal 5 MB per berkas, maksimal 3 berkas per laporan).
- **Batas karakter deskripsi laporan: 5000 karakter.** Batas ini ditegakkan di sisi klien (umpan balik langsung) dan di sisi server.
- **Warna indikator mencerminkan makna** — merah khusus untuk status Kritis/Ditolak/Hapus, bukan sekedar hiasan.
- Nomor laporan menggunakan UUID (v7) sebagai pengenal publik, sehingga ID basis data tidak terekspos ke pengguna.

---

## Lisensi

Proyek ini dibuat untuk keperluan pembelajaran dan tugas akhir sekolah. Dependensi pihak ketiga mengikuti lisensi masing-masing (Laravel: MIT).
