<section class="dashboard-stats" aria-label="Ringkasan laporan">
    <a href="{{ url('/dashboard') }}" class="stat-card {{ $_statusFilter === null ? 'is-active' : '' }}">
        <div class="stat-card-header">
            <span class="stat-label">TOTAL LAPORAN</span>
            <span class="stat-icon stat-icon-total" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="5" y="3" width="14" height="18" rx="2"/><path d="M9 7h6M9 11h6M9 15h4"/></svg>
            </span>
        </div>
        <div class="stat-card-body">
            <span class="stat-value">{{ $_allReportsCount }}</span>
            <span class="stat-description">{{ $_isStaff ? 'Semua laporan yang dapat diakses' : 'Semua laporan yang kamu buat' }}</span>
        </div>
        <span class="stat-card-link">Lihat semua laporan <span aria-hidden="true">→</span></span>
    </a>

    <a href="{{ url('/dashboard') }}?status=Pending" class="stat-card stat-card-pending {{ $_statusFilter === 'Pending' ? 'is-active' : '' }}">
        <div class="stat-card-header">
            <span class="stat-label">MENUNGGU</span>
            <span class="stat-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="8"/><path d="M12 8v4l2.5 2"/></svg>
            </span>
        </div>
        <div class="stat-card-body">
            <span class="stat-value">{{ $_statusCounts['Pending'] ?? 0 }}</span>
            <span class="stat-description">Sedang menunggu penanganan</span>
        </div>
        <span class="stat-card-link">Saring laporan <span aria-hidden="true">→</span></span>
    </a>

    <a href="{{ url('/dashboard') }}?status=Rejected" class="stat-card stat-card-rejected {{ $_statusFilter === 'Rejected' ? 'is-active' : '' }}">
        <div class="stat-card-header">
            <span class="stat-label">DITOLAK</span>
            <span class="stat-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="8"/><path d="m9 9 6 6M15 9l-6 6"/></svg>
            </span>
        </div>
        <div class="stat-card-body">
            <span class="stat-value">{{ $_statusCounts['Rejected'] ?? 0 }}</span>
            <span class="stat-description">Laporan yang ditolak</span>
        </div>
        <span class="stat-card-link">Saring laporan <span aria-hidden="true">→</span></span>
    </a>

    <a href="{{ url('/dashboard') }}?status=Resolved" class="stat-card stat-card-resolved {{ $_statusFilter === 'Resolved' ? 'is-active' : '' }}">
        <div class="stat-card-header">
            <span class="stat-label">SELESAI</span>
            <span class="stat-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="8"/><path d="m8.5 12 2.3 2.3 4.7-4.7"/></svg>
            </span>
        </div>
        <div class="stat-card-body">
            <span class="stat-value">{{ $_statusCounts['Resolved'] ?? 0 }}</span>
            <span class="stat-description">Laporan selesai ditangani</span>
        </div>
        <span class="stat-card-link">Saring laporan <span aria-hidden="true">→</span></span>
    </a>
</section>
