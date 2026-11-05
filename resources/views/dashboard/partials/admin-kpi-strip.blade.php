{{--
    KPI row for the admin dashboard. Uses the same tile component as the
    siswa/petugas dashboards (label, value, description, link with arrow) so the
    three roles read as one product. Props: $counts.
--}}
@php
    $counts = $counts ?? [];
    $cells = [
        [
            'label' => 'Total pengguna',
            'value' => $counts['users'] ?? 0,
            'note' => 'Seluruh akun terdaftar',
            'link' => 'Kelola pengguna',
            'url' => route('admin.users.index'),
        ],
        [
            'label' => 'Petugas',
            'value' => $counts['petugas'] ?? 0,
            'note' => 'Akun yang menangani laporan',
            'link' => 'Lihat petugas',
            'url' => route('admin.users.index', ['role' => 'petugas']),
        ],
        [
            'label' => 'Siswa',
            'value' => $counts['students'] ?? 0,
            'note' => 'Akun pelapor fasilitas',
            'link' => 'Lihat siswa',
            'url' => route('admin.users.index', ['role' => 'siswa']),
        ],
        [
            'label' => 'Fasilitas',
            'value' => $counts['facilities'] ?? 0,
            'note' => 'Fasilitas yang dapat dilaporkan',
            'link' => 'Kelola fasilitas',
            'url' => route('admin.facilities.index'),
        ],
        [
            'label' => 'Kategori',
            'value' => $counts['categories'] ?? 0,
            'note' => 'Kategori laporan tersedia',
            'link' => 'Kelola kategori',
            'url' => route('admin.categories.index'),
        ],
    ];
@endphp

<section class="dashboard-stats admin-stats" aria-label="Ringkasan data induk">
    @foreach ($cells as $cell)
        <a href="{{ $cell['url'] }}" class="stat-card">
            <span class="stat-label">{{ $cell['label'] }}</span>
            <span class="stat-value">{{ number_format($cell['value'], 0, ',', '.') }}</span>
            <span class="stat-description">{{ $cell['note'] }}</span>
            <span class="stat-card-link">{{ $cell['link'] }} <span aria-hidden="true">&rarr;</span></span>
        </a>
    @endforeach
</section>
