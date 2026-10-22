@extends('layouts.app')

@push('styles')
    @vite('resources/css/dashboard.css')
@endpush

@section('title', 'Dashboard Staff')
@section('page-title', 'Dashboard')
@section('page-description', 'Pantau dan kelola laporan fasilitas dari seluruh siswa.')

@section('content')
<div class="dashboard-widget staff-dashboard-widget">
    @include('dashboard.partials.stats')

    <section id="staff-reports" class="dashboard-card reports-card staff-reports-card" tabindex="-1">
        <div class="dashboard-card-header staff-reports-heading">
            <div>
                <h2 class="dashboard-card-title">Seluruh Laporan</h2>
                <p class="dashboard-card-subtitle">Cari, saring, dan urutkan laporan berdasarkan kebutuhan penanganan.</p>
            </div>
            <span class="staff-report-total">{{ number_format($_totalReports, 0, ',', '.') }} hasil</span>
        </div>

        <form method="GET" action="{{ url('/dashboard') }}" class="staff-filter-form">
            <div class="staff-filter-field staff-filter-field--search">
                <label for="report-search">Cari laporan</label>
                <input type="search" id="report-search" name="q" value="{{ $_search }}" placeholder="Judul, lokasi, nama, email, atau ID">
            </div>

            <div class="staff-filter-field">
                <label for="reporter-filter">Pengguna</label>
                <select id="reporter-filter" name="reporter">
                    <option value="">Semua pengguna</option>
                    @foreach ($_reporters as $reporter)
                        <option value="{{ $reporter->id }}" {{ (string) $_reporterFilter === (string) $reporter->id ? 'selected' : '' }}>{{ $reporter->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="staff-filter-field">
                <label for="staff-status-filter">Status</label>
                <select id="staff-status-filter" name="status">
                    <option value="">Semua status</option>
                    <option value="Pending" {{ $_statusFilter === 'Pending' ? 'selected' : '' }}>Menunggu</option>
                    <option value="Rejected" {{ $_statusFilter === 'Rejected' ? 'selected' : '' }}>Ditolak</option>
                    <option value="Resolved" {{ $_statusFilter === 'Resolved' ? 'selected' : '' }}>Selesai</option>
                </select>
            </div>

            <div class="staff-filter-field">
                <label for="severity-filter">Tingkat keparahan</label>
                <select id="severity-filter" name="severity">
                    <option value="">Semua tingkat</option>
                    <option value="Critical" {{ $_severityFilter === 'Critical' ? 'selected' : '' }}>Kritis</option>
                    <option value="High" {{ $_severityFilter === 'High' ? 'selected' : '' }}>Tinggi</option>
                    <option value="Medium" {{ $_severityFilter === 'Medium' ? 'selected' : '' }}>Sedang</option>
                    <option value="Low" {{ $_severityFilter === 'Low' ? 'selected' : '' }}>Rendah</option>
                </select>
            </div>

            <div class="staff-filter-field">
                <label for="facility-filter">Fasilitas</label>
                <select id="facility-filter" name="facility">
                    <option value="">Semua fasilitas</option>
                    @foreach ($_facilities as $facility)
                        <option value="{{ $facility->id }}" {{ (string) $_facilityFilter === (string) $facility->id ? 'selected' : '' }}>{{ $facility->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="staff-filter-field">
                <label for="category-filter">Kategori</label>
                <select id="category-filter" name="category">
                    <option value="">Semua kategori</option>
                    @foreach ($_categories as $category)
                        <option value="{{ $category->id }}" {{ (string) $_categoryFilter === (string) $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="staff-filter-field staff-filter-field--sort">
                <label for="staff-sort-filter">Urutkan berdasarkan</label>
                <select id="staff-sort-filter" name="sort">
                    <option value="newest" {{ $_sortFilter === 'newest' ? 'selected' : '' }}>Aktivitas terbaru</option>
                    <option value="oldest" {{ $_sortFilter === 'oldest' ? 'selected' : '' }}>Laporan terlama</option>
                    <option value="user_asc" {{ $_sortFilter === 'user_asc' ? 'selected' : '' }}>Nama pengguna A–Z</option>
                    <option value="user_desc" {{ $_sortFilter === 'user_desc' ? 'selected' : '' }}>Nama pengguna Z–A</option>
                    <option value="severity_high" {{ $_sortFilter === 'severity_high' ? 'selected' : '' }}>Keparahan tertinggi</option>
                    <option value="severity_low" {{ $_sortFilter === 'severity_low' ? 'selected' : '' }}>Keparahan terendah</option>
                </select>
            </div>

            <div class="staff-filter-actions">
                <button type="submit" class="filter-apply-button">Terapkan filter</button>
                <a href="{{ url('/dashboard') }}" class="staff-filter-reset">Reset</a>
            </div>
        </form>

        @if ($_hasFilters)
            <div class="active-filter-note">
                <span>Daftar laporan mengikuti filter yang dipilih.</span>
                <a href="{{ url('/dashboard') }}">Hapus semua filter</a>
            </div>
        @endif

        <div class="reports-table-wrapper">
            <table class="reports-table staff-reports-table">
                <thead>
                    <tr>
                        <th scope="col">Laporan</th>
                        <th scope="col">Pelapor</th>
                        <th scope="col">Fasilitas</th>
                        <th scope="col">Kategori</th>
                        <th scope="col">Keparahan</th>
                        <th scope="col">Status</th>
                        <th scope="col">Dibuat</th>
                        <th scope="col">Aktivitas terakhir</th>
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
                            $severityClass = [
                                'Critical' => 'severity-critical',
                                'High' => 'severity-high',
                                'Medium' => 'severity-medium',
                                'Low' => 'severity-low',
                            ][$report->severity] ?? 'severity-low';
                            $severityLabel = [
                                'Critical' => 'Kritis',
                                'High' => 'Tinggi',
                                'Medium' => 'Sedang',
                                'Low' => 'Rendah',
                            ][$report->severity] ?? $report->severity;
                            $lastActivity = $report->responses_max_created_at ?? $report->updated_at ?? $report->created_at;
                        @endphp
                        <tr>
                            <td class="staff-report-cell">
                                <strong class="staff-report-title">{{ $report->title }}</strong>
                                <span class="staff-report-meta">#{{ $report->id }} · {{ $report->location_description }}</span>
                            </td>
                            <td>
                                <strong class="staff-table-primary">{{ $report->user?->name ?? 'Pengguna tidak tersedia' }}</strong>
                                @if ($report->user?->email)
                                    <span class="staff-table-secondary">{{ $report->user->email }}</span>
                                @endif
                            </td>
                            <td class="facility-name">{{ $report->facility?->name ?? 'Fasilitas tidak tersedia' }}</td>
                            <td>{{ $report->category?->name ?? 'Belum dikategorikan' }}</td>
                            <td><span class="severity-badge {{ $severityClass }}">{{ $severityLabel }}</span></td>
                            <td><span class="status-badge {{ $statusClass }}">{{ $statusLabel }}</span></td>
                            <td class="report-date"><time data-local-time datetime="{{ \Illuminate\Support\Carbon::parse($report->created_at)->toIso8601String() }}">{{ \Illuminate\Support\Carbon::parse($report->created_at)->locale('id')->translatedFormat('d M Y, H:i') }}</time></td>
                            <td class="report-date"><time data-local-time datetime="{{ \Illuminate\Support\Carbon::parse($lastActivity)->toIso8601String() }}">{{ \Illuminate\Support\Carbon::parse($lastActivity)->locale('id')->translatedFormat('d M Y, H:i') }}</time></td>
                            <td>
                                <a class="staff-view-report" href="{{ route('reports.showDetail', ['uuid' => $report->identifier]) }}">Lihat detail <span aria-hidden="true">→</span></a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="table-empty">
                                <span class="table-empty-icon" aria-hidden="true">⌕</span>
                                <strong>Tidak ada laporan yang cocok</strong>
                                <span>Coba ubah kata kunci atau filter yang digunakan.</span>
                                <a href="{{ url('/dashboard') }}" class="empty-clear-filter">Reset semua filter</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="reports-footer">
            <span>Menampilkan <strong>{{ $_indexedReports }}</strong> dari <strong>{{ $_totalReports }}</strong> laporan sesuai filter.</span>
            <div class="pagination">
                <form method="GET" action="{{ url('/dashboard') }}">
                    @foreach (request()->except('page') as $key => $value)
                        @if (is_scalar($value))
                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                        @endif
                    @endforeach
                    @if ($_currentPage > 0)
                        <button type="submit" name="page" value="{{ $_currentPage - 1 }}" class="pagination-button">Sebelumnya</button>
                    @endif
                    @if (($_currentPage + 1) * 10 < $_totalReports)
                        <button type="submit" name="page" value="{{ $_currentPage + 1 }}" class="pagination-button">Berikutnya</button>
                    @endif
                </form>
            </div>
        </div>
    </section>

    <section class="staff-dashboard-analytics" aria-label="Analisis laporan">
        @include('dashboard.partials.category-card')
        @include('dashboard.partials.facility-card')
    </section>
</div>
@endsection
