@extends('layouts.app')

@push('styles')
    {{-- dashboard.css supplies the shared .stat-card KPI tiles so the admin row
         is the same component the siswa/petugas dashboards use. --}}
    @vite('resources/css/pages/dashboard.css')
    @vite('resources/css/pages/admin.css')
@endpush

@section('title', 'Dashboard Admin')
@section('page-title', 'Ringkasan Fasilitas')
@section('page-description', $_finding ?? 'Statistik dan ringkasan data fasilitas sekolah.')

@section('content')
@php
    $roleChart = $_roleChart ?? [];
    $facilityChart = $_facilityChart ?? [];
    $categoryChart = $_categoryChart ?? [];
    $classroomChart = $_studentsByClassroom ?? [];
    $recentUsers = $_recentUsers ?? collect();
    $recentFacilities = $_recentFacilities ?? collect();
    $recentCategories = $_recentCategories ?? collect();

    $roleTotal = collect($roleChart)->sum('total');
    $hasRoleData = $roleTotal > 0;
    $hasFacilityData = collect($facilityChart)->sum('total') > 0;
    $hasCategoryData = collect($categoryChart)->sum('total') > 0;
    $hasClassroomData = collect($classroomChart)->sum('total') > 0;

    $roleLabels = ['siswa' => 'Siswa', 'petugas' => 'Petugas', 'admin' => 'Admin'];
    $roleClass = ['siswa' => 'admin-role--siswa', 'petugas' => 'admin-role--petugas', 'admin' => 'admin-role--admin'];

    // Kept in a variable because the @json directive cannot parse a multi-line array literal.
    $chartPayload = [
        'roles' => $roleChart,
        'facilities' => $facilityChart,
        'categories' => $categoryChart,
        'classrooms' => $classroomChart,
    ];
@endphp

<div class="admin-widget" data-admin-dashboard>
    @include('dashboard.partials.admin-kpi-strip', ['counts' => $_counts])

    <section class="admin-health" aria-label="Kelengkapan dan pemakaian data">
        @include('dashboard.partials.admin-coverage', ['coverage' => $_coverage ?? []])
        @include('dashboard.partials.admin-attention', ['dataGaps' => $_dataGaps ?? []])
    </section>

    <section class="admin-chart-grid" aria-label="Statistik data">
        <article class="admin-card admin-chart-card">
            <div class="admin-card-heading">
                <div>
                    <h2 class="admin-card-title">Siswa per Kelas</h2>
                    <p class="admin-card-subtitle">Sebaran siswa pada kelas terbesar.</p>
                </div>
            </div>

            @if ($hasClassroomData)
                <div class="admin-chart-canvas">
                    <canvas id="admin-classroom-chart" role="img" aria-label="Grafik jumlah siswa per kelas"></canvas>
                </div>
            @else
                <div class="admin-empty">
                    <strong>Belum ada kelas terisi</strong>
                    Kelas muncul setelah siswa diberi kelas.
                </div>
            @endif
        </article>

        <article class="admin-card admin-chart-card">
            <div class="admin-card-heading">
                <div>
                    <h2 class="admin-card-title">Komposisi Pengguna</h2>
                    <p class="admin-card-subtitle">Sebaran akun berdasarkan peran.</p>
                </div>
            </div>

            @if ($hasRoleData)
                <div class="admin-chart-canvas admin-chart-canvas--doughnut">
                    <canvas id="admin-role-chart" role="img" aria-label="Grafik komposisi peran pengguna"></canvas>
                </div>
                <ul class="admin-legend">
                    @foreach ($roleChart as $row)
                        @php $share = $roleTotal > 0 ? round(($row['total'] / $roleTotal) * 100) : 0; @endphp
                        <li class="admin-legend-item">
                            <span class="admin-legend-swatch admin-legend-swatch--{{ strtolower($row['label']) }}" aria-hidden="true"></span>
                            <span class="admin-legend-label">{{ $row['label'] }}</span>
                            <span class="admin-legend-value">{{ number_format($row['total'], 0, ',', '.') }} · {{ $share }}%</span>
                        </li>
                    @endforeach
                </ul>
            @else
                <div class="admin-empty">
                    <strong>Belum ada pengguna</strong>
                    Tambahkan akun untuk melihat komposisinya.
                </div>
            @endif
        </article>

        <article class="admin-card admin-chart-card">
            <div class="admin-card-heading">
                <div>
                    <h2 class="admin-card-title">Fasilitas Paling Sering Dilaporkan</h2>
                    <p class="admin-card-subtitle">Enam fasilitas dengan laporan terbanyak.</p>
                </div>
            </div>

            @if ($hasFacilityData)
                <div class="admin-chart-canvas">
                    <canvas id="admin-facility-chart" role="img" aria-label="Grafik jumlah laporan per fasilitas"></canvas>
                </div>
            @else
                <div class="admin-empty">
                    <strong>Belum ada laporan</strong>
                    Grafik muncul setelah siswa mengirim laporan.
                </div>
            @endif
        </article>

        <article class="admin-card admin-chart-card">
            <div class="admin-card-heading">
                <div>
                    <h2 class="admin-card-title">Kategori Paling Sering Dilaporkan</h2>
                    <p class="admin-card-subtitle">Enam kategori dengan laporan terbanyak.</p>
                </div>
            </div>

            @if ($hasCategoryData)
                <div class="admin-chart-canvas">
                    <canvas id="admin-category-chart" role="img" aria-label="Grafik jumlah laporan per kategori"></canvas>
                </div>
            @else
                <div class="admin-empty">
                    <strong>Belum ada laporan</strong>
                    Grafik muncul setelah siswa mengirim laporan.
                </div>
            @endif
        </article>
    </section>

    <section class="admin-recent-grid" aria-label="Data terbaru">
        <article class="admin-card">
            <div class="admin-card-heading">
                <div>
                    <h2 class="admin-card-title">Pengguna Terbaru</h2>
                    <p class="admin-card-subtitle">Lima akun terakhir dibuat.</p>
                </div>
                <a href="{{ route('admin.users.index') }}" class="admin-button admin-button--secondary admin-button--small">Lihat semua</a>
            </div>

            @if ($recentUsers->isEmpty())
                <div class="admin-empty">
                    <strong>Belum ada pengguna</strong>
                    Akun baru akan muncul di sini.
                </div>
            @else
                <ul class="admin-list">
                    @foreach ($recentUsers as $user)
                        <li class="admin-list-item">
                            <span class="admin-list-copy">
                                <strong>{{ $user->name }}</strong>
                                <span>{{ $user->isStudent() ? collect([$user->nis ? 'NIS '.$user->nis : null, $user->classroom])->filter()->implode(' · ') ?: 'Identitas belum lengkap' : ($user->email ?: 'Email belum tersedia') }}</span>
                            </span>
                            <span class="admin-role {{ $roleClass[$user->role] ?? 'admin-role--siswa' }}">
                                {{ $roleLabels[$user->role] ?? ucfirst($user->role) }}
                            </span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </article>

        <article class="admin-card">
            <div class="admin-card-heading">
                <div>
                    <h2 class="admin-card-title">Fasilitas Terbaru</h2>
                    <p class="admin-card-subtitle">Lima fasilitas terakhir ditambahkan.</p>
                </div>
                <a href="{{ route('admin.facilities.index') }}" class="admin-button admin-button--secondary admin-button--small">Lihat semua</a>
            </div>

            @if ($recentFacilities->isEmpty())
                <div class="admin-empty">
                    <strong>Belum ada fasilitas</strong>
                    Tambahkan fasilitas agar siswa dapat melaporkannya.
                </div>
            @else
                <ul class="admin-list">
                    @foreach ($recentFacilities as $facility)
                        <li class="admin-list-item">
                            <span class="admin-list-copy">
                                <strong>{{ $facility->name }}</strong>
                            </span>
                            <a href="{{ route('admin.facilities.edit', $facility) }}" class="admin-button admin-button--secondary admin-button--small">Ubah</a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </article>

        <article class="admin-card">
            <div class="admin-card-heading">
                <div>
                    <h2 class="admin-card-title">Kategori Terbaru</h2>
                    <p class="admin-card-subtitle">Lima kategori terakhir ditambahkan.</p>
                </div>
                <a href="{{ route('admin.categories.index') }}" class="admin-button admin-button--secondary admin-button--small">Lihat semua</a>
            </div>

            @if ($recentCategories->isEmpty())
                <div class="admin-empty">
                    <strong>Belum ada kategori</strong>
                    Tambahkan kategori agar laporan mudah dikelompokkan.
                </div>
            @else
                <ul class="admin-list">
                    @foreach ($recentCategories as $category)
                        <li class="admin-list-item">
                            <span class="admin-list-copy">
                                <strong>{{ $category->name }}</strong>
                            </span>
                            <a href="{{ route('admin.categories.edit', $category) }}" class="admin-button admin-button--secondary admin-button--small">Ubah</a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </article>
    </section>

    <script type="application/json" id="admin-chart-data">@json($chartPayload, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)</script>
</div>
@endsection

@push('scripts')
    @vite('resources/js/admin-dashboard.js')
@endpush
