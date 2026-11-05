{{--
    KPI strip. One container split by 1px dividers into equal columns rather
    than five separate tiles: the studied screens give a KPI no tile of its own
    (app-screens.md §5), and the value leads at 26px under a 12px grey label.
    Props: $counts (users, petugas, students, facilities, categories).
--}}
@php
    $counts = $counts ?? [];
    $cells = [
        [
            'label' => 'Total pengguna',
            'value' => $counts['users'] ?? 0,
            'note' => 'Seluruh akun terdaftar',
            'url' => route('admin.users.index'),
        ],
        [
            'label' => 'Petugas',
            'value' => $counts['petugas'] ?? 0,
            'note' => 'Menangani laporan',
            'url' => route('admin.users.index', ['role' => 'petugas']),
        ],
        [
            'label' => 'Siswa',
            'value' => $counts['students'] ?? 0,
            'note' => 'Akun pelapor',
            'url' => route('admin.users.index', ['role' => 'siswa']),
        ],
        [
            'label' => 'Fasilitas',
            'value' => $counts['facilities'] ?? 0,
            'note' => 'Dapat dilaporkan',
            'url' => route('admin.facilities.index'),
        ],
        [
            'label' => 'Kategori',
            'value' => $counts['categories'] ?? 0,
            'note' => 'Kelompok laporan',
            'url' => route('admin.categories.index'),
        ],
    ];
@endphp

<section class="admin-kpi-strip" aria-label="Ringkasan data induk">
    @foreach ($cells as $cell)
        <a href="{{ $cell['url'] }}" class="admin-kpi">
            <span class="admin-kpi-label">{{ $cell['label'] }}</span>
            <span class="admin-kpi-value">{{ number_format($cell['value'], 0, ',', '.') }}</span>
            <span class="admin-kpi-note">{{ $cell['note'] }}</span>
        </a>
    @endforeach
</section>
