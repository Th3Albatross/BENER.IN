@extends('layouts.app')

@push('styles')
    @vite('resources/css/dashboard.css')
@endpush

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')
@section('page-description', 'Pantau status dan perkembangan laporan fasilitasmu.')

@section('content')
<div class="dashboard-widget">
    <section class="dashboard-stats" aria-label="Ringkasan laporan">
        <a href="{{ url('/dashboard') }}" class="stat-card {{ $_statusFilter === null ? 'is-active' : '' }}">
            <div class="stat-card-header">
                <span class="stat-label">TOTAL LAPORAN</span>
                <span class="stat-icon stat-icon-total" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <rect x="5" y="3" width="14" height="18" rx="2"/>
                        <path d="M9 7h6M9 11h6M9 15h4"/>
                    </svg>
                </span>
            </div>
            <div class="stat-card-body">
                <span class="stat-value">{{ $_allReportsCount }}</span>
                <span class="stat-description">Semua laporan yang kamu buat</span>
            </div>
            <span class="stat-card-link">Lihat semua laporan <span aria-hidden="true">→</span></span>
        </a>

        <a href="{{ url('/dashboard') }}?status=Pending" class="stat-card stat-card-pending {{ $_statusFilter === 'Pending' ? 'is-active' : '' }}">
            <div class="stat-card-header">
                <span class="stat-label">MENUNGGU</span>
                <span class="stat-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <circle cx="12" cy="12" r="8"/>
                        <path d="M12 8v4l2.5 2"/>
                    </svg>
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
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <circle cx="12" cy="12" r="8"/>
                        <path d="m9 9 6 6M15 9l-6 6"/>
                    </svg>
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
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <circle cx="12" cy="12" r="8"/>
                        <path d="m8.5 12 2.3 2.3 4.7-4.7"/>
                    </svg>
                </span>
            </div>
            <div class="stat-card-body">
                <span class="stat-value">{{ $_statusCounts['Resolved'] ?? 0 }}</span>
                <span class="stat-description">Laporan selesai ditangani</span>
            </div>
            <span class="stat-card-link">Saring laporan <span aria-hidden="true">→</span></span>
        </a>
    </section>

    <section class="dashboard-main-grid">
        <div class="dashboard-card reports-card">
            <div class="dashboard-card-header reports-card-heading">
                <div>
                    <h2 class="dashboard-card-title">Daftar Laporan</h2>
                    <p class="dashboard-card-subtitle">Laporan fasilitas yang kamu kirimkan</p>
                </div>

                <form method="GET" action="{{ url('/dashboard') }}" class="report-filter-form">
                    <label for="status-filter">Status</label>
                    <select name="status" id="status-filter" class="report-filter-select">
                        <option value="" {{ $_statusFilter === null ? 'selected' : '' }}>Semua status</option>
                        <option value="Pending" {{ $_statusFilter === 'Pending' ? 'selected' : '' }}>Menunggu</option>
                        <option value="Rejected" {{ $_statusFilter === 'Rejected' ? 'selected' : '' }}>Ditolak</option>
                        <option value="Resolved" {{ $_statusFilter === 'Resolved' ? 'selected' : '' }}>Selesai</option>
                    </select>
                    <button type="submit" class="filter-apply-button">Terapkan</button>
                </form>
            </div>

            @if ($_statusFilter !== null)
                <div class="active-filter-note">
                    <span>Menampilkan laporan dengan status <strong>{{ ['Pending' => 'Menunggu', 'Rejected' => 'Ditolak', 'Resolved' => 'Selesai'][$_statusFilter] }}</strong></span>
                    <a href="{{ url('/dashboard') }}">Hapus filter</a>
                </div>
            @endif

            <div class="reports-table-wrapper">
                <table class="reports-table">
                    <thead>
                        <tr>
                            <th scope="col">ID</th>
                            <th scope="col">Nama fasilitas</th>
                            <th scope="col">Kategori laporan</th>
                            <th scope="col">Status</th>
                            <th scope="col">Tanggal dibuat</th>
                            <th scope="col">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($_reports as $report)
                            @php
                                $statusClass = [
                                    'Pending' => 'status-pending',
                                    'Rejected' => 'status-rejected',
                                    'Resolved' => 'status-resolved',
                                ][$report->status] ?? 'status-pending';
                                $statusLabel = [
                                    'Pending' => 'Menunggu',
                                    'Rejected' => 'Ditolak',
                                    'Resolved' => 'Selesai',
                                ][$report->status] ?? $report->status;
                            @endphp
                            <tr>
                                <td class="report-id">#{{ $report->id }}</td>
                                <td class="facility-name">{{ $report->facilities_name }}</td>
                                <td>{{ $report->categories_name ?? 'Belum dikategorikan' }}</td>
                                <td><span class="status-badge {{ $statusClass }}">{{ $statusLabel }}</span></td>
                                <td class="report-date">
                                    {{ \Illuminate\Support\Carbon::parse($report->created_at)->locale('id')->translatedFormat('d M Y, H:i') }}
                                </td>
                                <td>
                                    <div class="report-actions">
                                        @if ($report->status === 'Pending')
                                            <a href="{{ route('reports.edit', ['uuid' => $report->identifier]) }}" class="report-action report-action-edit">Edit</a>
                                        @endif
                                        <a href="{{ route('reports.show', ['uuid' => $report->identifier]) }}" class="report-action report-action-view">Lihat</a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="table-empty">
                                    <span class="table-empty-icon" aria-hidden="true">⌕</span>
                                    <strong>{{ $_statusFilter ? 'Tidak ada laporan dengan status ini' : 'Belum ada laporan' }}</strong>
                                    <span>{{ $_statusFilter ? 'Coba pilih status lain atau hapus filter.' : 'Laporan yang kamu buat akan muncul di sini.' }}</span>
                                    @if ($_statusFilter !== null)
                                        <a href="{{ url('/dashboard') }}" class="empty-clear-filter">Tampilkan semua laporan</a>
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="reports-footer">
                <span>Menampilkan <strong>{{ $_indexedReports }}</strong> dari <strong>{{ $_totalReports }}</strong> laporan{{ $_statusFilter ? ' yang difilter' : '' }}</span>
                <div class="pagination">
                    <form method="GET" action="{{ url('/dashboard') }}">
                        @if ($_statusFilter !== null)
                            <input type="hidden" name="status" value="{{ $_statusFilter }}">
                        @endif
                        @if ($_currentPage > 0)
                            <button type="submit" name="page" value="{{ $_currentPage - 1 }}" class="pagination-button">Sebelumnya</button>
                        @endif
                        @if (($_currentPage + 1) * 10 < $_totalReports)
                            <button type="submit" name="page" value="{{ $_currentPage + 1 }}" class="pagination-button">Berikutnya</button>
                        @endif
                    </form>
                </div>
            </div>
        </div>

        <aside class="dashboard-side">
            <div class="dashboard-card quick-actions-card">
                <div class="dashboard-card-header">
                    <div>
                        <h2 class="dashboard-card-title">Aksi Cepat</h2>
                        <p class="dashboard-card-subtitle">Buat laporan baru dalam beberapa langkah</p>
                    </div>
                </div>
                <a href="{{ route('reports.create') }}" class="quick-action-button">
                    <span class="quick-action-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="M12 5v14M5 12h14"/>
                        </svg>
                    </span>
                    <span>Buat laporan baru</span>
                    <svg class="quick-action-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path d="m9 18 6-6-6-6"/>
                    </svg>
                </a>
            </div>

            <div class="dashboard-card category-card">
                <div class="dashboard-card-header">
                    <div>
                        <h2 class="dashboard-card-title">Laporan per Kategori</h2>
                        <p class="dashboard-card-subtitle">Kategori yang paling sering dipilih</p>
                    </div>
                </div>
                @if ($_categoryStats->isNotEmpty())
                    <div class="category-list">
                        @foreach ($_categoryStats as $categoryStat)
                            <div class="category-item">
                                <div class="category-info">
                                    <span title="{{ $categoryStat->category_name }}">{{ $categoryStat->category_name }}</span>
                                    <strong>{{ $categoryStat->total }}</strong>
                                </div>
                                <div class="category-progress" role="progressbar" aria-label="Jumlah laporan kategori {{ $categoryStat->category_name }}" aria-valuemin="0" aria-valuemax="{{ $_maxCategoryReports }}" aria-valuenow="{{ $categoryStat->total }}">
                                    <div class="category-progress-fill" style="width: {{ max(5, (int) round(($categoryStat->total / $_maxCategoryReports) * 100)) }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="widget-empty">Belum ada data kategori untuk ditampilkan.</p>
                @endif
            </div>

            <div class="dashboard-card facilities-card">
                <div class="dashboard-card-header">
                    <div>
                        <h2 class="dashboard-card-title">Fasilitas Paling Sering Dilaporkan</h2>
                        <p class="dashboard-card-subtitle">Berdasarkan laporan yang kamu buat</p>
                    </div>
                </div>
                @if ($_facilityStats->isNotEmpty())
                    <div class="facility-list">
                        @foreach ($_facilityStats as $index => $facilityStat)
                            <div class="facility-item">
                                <div class="facility-rank">{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}</div>
                                <div class="facility-details">
                                    <span class="facility-title" title="{{ $facilityStat->facility_name }}">{{ $facilityStat->facility_name }}</span>
                                    <span class="facility-count">{{ $facilityStat->total }} laporan</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="widget-empty">Belum ada data fasilitas untuk ditampilkan.</p>
                @endif
            </div>
        </aside>
    </section>
</div>
@endsection
