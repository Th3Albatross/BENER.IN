@extends('layouts.app')

@push('styles')
    @vite('resources/css/pages/dashboard.css')
@endpush

@section('title', 'Dashboard')
@section('page-title', 'Laporan Saya')
@section('page-description', 'Pantau status dan perkembangan laporan fasilitasmu.')

@section('content')
<div class="dashboard-widget student-dashboard-widget">
    @include('dashboard.partials.stats')

    <section class="dashboard-card reports-card student-reports-card" aria-labelledby="student-reports-title">
        <div class="dashboard-card-header student-reports-heading">
            <div>
                <h2 id="student-reports-title" class="dashboard-card-title">Daftar Laporan</h2>
                <p class="dashboard-card-subtitle">Laporan fasilitas yang kamu kirimkan.</p>
            </div>

            <div class="student-report-heading-actions">
                <span class="staff-report-total">{{ number_format($_totalReports, 0, ',', '.') }} laporan</span>
                <a href="{{ route('reports.showCreate') }}" class="student-new-report-button">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path d="M12 5v14M5 12h14"/>
                    </svg>
                    <span>Buat laporan</span>
                </a>
            </div>
        </div>

        <form method="GET" action="{{ url('/dashboard') }}" class="student-status-filter-form">
            <div class="student-status-filter-field">
                <label for="student-status-filter">Filter status</label>
                <select name="status" id="student-status-filter" class="report-filter-select">
                    <option value="" {{ $_statusFilter === null ? 'selected' : '' }}>Semua status</option>
                    <option value="Pending" {{ $_statusFilter === 'Pending' ? 'selected' : '' }}>Menunggu</option>
                    <option value="Rejected" {{ $_statusFilter === 'Rejected' ? 'selected' : '' }}>Ditolak</option>
                    <option value="Resolved" {{ $_statusFilter === 'Resolved' ? 'selected' : '' }}>Selesai</option>
                </select>
            </div>
            <button type="submit" class="filter-apply-button">Terapkan</button>
            @if ($_statusFilter !== null)
                <a href="{{ url('/dashboard') }}" class="staff-filter-reset">Reset</a>
            @endif
        </form>

        @if ($_statusFilter !== null)
            <div class="active-filter-note">
                <span>Menampilkan laporan dengan status <strong>{{ ['Pending' => 'Menunggu', 'Rejected' => 'Ditolak', 'Resolved' => 'Selesai'][$_statusFilter] }}</strong>.</span>
                <a href="{{ url('/dashboard') }}">Hapus filter</a>
            </div>
        @endif

        <div class="reports-table-wrapper">
            <table class="reports-table student-reports-table">
                <thead>
                    <tr>
                        <th scope="col">Laporan</th>
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
                            <td class="staff-report-cell" data-label="Laporan">
                                <strong class="staff-report-title" title="{{ $report->title }}">{{ $report->title }}</strong>
                                <span class="staff-report-meta">#{{ $report->id }} · {{ $report->location_description }}</span>
                            </td>
                            <td class="facility-name" data-label="Fasilitas">{{ $report->facility?->name ?? 'Fasilitas tidak tersedia' }}</td>
                            <td data-label="Kategori">{{ $report->category?->name ?? 'Belum dikategorikan' }}</td>
                            <td data-label="Keparahan"><span class="severity-badge {{ $severityClass }}">{{ $severityLabel }}</span></td>
                            <td data-label="Status"><span class="status-badge {{ $statusClass }}">{{ $statusLabel }}</span></td>
                            <td class="report-date" data-label="Dibuat"><time data-local-time datetime="{{ \Illuminate\Support\Carbon::parse($report->created_at)->toIso8601String() }}">{{ \Illuminate\Support\Carbon::parse($report->created_at)->locale('id')->translatedFormat('d M Y, H:i') }}</time></td>
                            <td class="report-date" data-label="Aktivitas terakhir"><time data-local-time datetime="{{ \Illuminate\Support\Carbon::parse($lastActivity)->toIso8601String() }}">{{ \Illuminate\Support\Carbon::parse($lastActivity)->locale('id')->translatedFormat('d M Y, H:i') }}</time></td>
                            <td data-label="Aksi">
                                <div class="student-report-actions">
                                    <a class="staff-view-report" href="{{ route('reports.showDetail', ['uuid' => $report->identifier]) }}">Detail <span aria-hidden="true">→</span></a>
                                    @if ($report->status === 'Pending')
                                        <a class="student-edit-report" href="{{ route('reports.showEdit', ['uuid' => $report->identifier]) }}">Edit</a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="table-empty">
                                <span class="table-empty-icon" aria-hidden="true">⌕</span>
                                <strong>{{ $_statusFilter ? 'Tidak ada laporan dengan status ini' : 'Belum ada laporan' }}</strong>
                                <span>{{ $_statusFilter ? 'Coba pilih status lain atau reset filter.' : 'Laporan yang kamu kirim akan muncul di sini.' }}</span>
                                @if ($_statusFilter !== null)
                                    <a href="{{ url('/dashboard') }}" class="empty-clear-filter">Tampilkan semua laporan</a>
                                @else
                                    <a href="{{ route('reports.showCreate') }}" class="empty-clear-filter">Buat laporan pertama</a>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="reports-footer">
            <span>Menampilkan <strong>{{ $_indexedReports }}</strong> dari <strong>{{ $_totalReports }}</strong> laporan{{ $_statusFilter ? ' yang difilter' : '' }}.</span>
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
    </section>

    <section class="staff-dashboard-analytics" aria-label="Analisis laporan">
        @include('dashboard.partials.category-card')
        @include('dashboard.partials.facility-card')
    </section>
</div>
@endsection
