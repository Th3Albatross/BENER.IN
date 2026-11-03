{{--
    KPI row. The value leads; the status colour sits on a small dot beside the
    label rather than on an icon in a tinted chip (app-screens.md §5: 0 of 23
    studied screens put an icon in a coloured circle on a KPI).
--}}
<section class="dashboard-stats" aria-label="Ringkasan laporan">
    <a href="{{ url('/dashboard') }}" class="stat-card {{ $_statusFilter === null ? 'is-active' : '' }}">
        <span class="stat-label">Total laporan</span>
        <span class="stat-value">{{ $_allReportsCount }}</span>
        <span class="stat-description">{{ $_isStaff ? 'Semua laporan yang dapat diakses' : 'Semua laporan yang kamu buat' }}</span>
        <span class="stat-card-link">Lihat semua laporan <span aria-hidden="true">→</span></span>
    </a>

    <a href="{{ url('/dashboard') }}?status=Pending" class="stat-card stat-card-pending {{ $_statusFilter === 'Pending' ? 'is-active' : '' }}">
        <span class="stat-label"><span class="stat-dot" aria-hidden="true"></span>Menunggu</span>
        <span class="stat-value">{{ $_statusCounts['Pending'] ?? 0 }}</span>
        <span class="stat-description">Sedang menunggu penanganan</span>
        <span class="stat-card-link">Saring laporan <span aria-hidden="true">→</span></span>
    </a>

    <a href="{{ url('/dashboard') }}?status=Rejected" class="stat-card stat-card-rejected {{ $_statusFilter === 'Rejected' ? 'is-active' : '' }}">
        <span class="stat-label"><span class="stat-dot" aria-hidden="true"></span>Ditolak</span>
        <span class="stat-value">{{ $_statusCounts['Rejected'] ?? 0 }}</span>
        <span class="stat-description">Laporan yang ditolak</span>
        <span class="stat-card-link">Saring laporan <span aria-hidden="true">→</span></span>
    </a>

    <a href="{{ url('/dashboard') }}?status=Resolved" class="stat-card stat-card-resolved {{ $_statusFilter === 'Resolved' ? 'is-active' : '' }}">
        <span class="stat-label"><span class="stat-dot" aria-hidden="true"></span>Selesai</span>
        <span class="stat-value">{{ $_statusCounts['Resolved'] ?? 0 }}</span>
        <span class="stat-description">Laporan selesai ditangani</span>
        <span class="stat-card-link">Saring laporan <span aria-hidden="true">→</span></span>
    </a>
</section>
