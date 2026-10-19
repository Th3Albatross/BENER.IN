@extends('layouts.app')

@push('styles')
    @vite('resources/css/discussion-index.css')
@endpush

@section('title', 'Diskusi')
@section('page-title', 'Diskusi')
@section('page-description', 'Temukan laporan dan buka percakapan dengan pelapor atau petugas.')

@section('content')
@php
    $reports = $_reports;
    $statusFilter = $_statusFilter ?? 'all';
    $statusCounts = $_statusCounts;
    $statusLabels = [
        'Pending' => 'Menunggu',
        'Resolved' => 'Selesai',
        'Rejected' => 'Ditolak',
    ];
@endphp

<div class="discussion-page">
    <section class="discussion-card">
        <div class="discussion-card-header">
            <form method="GET" action="{{ route('discussions.index') }}" class="discussion-filter-form">
                <label for="discussion-status-filter">Status</label>
                <select name="status" id="discussion-status-filter" class="discussion-filter-select">
                    <option value="all" {{ $statusFilter === 'all' ? 'selected' : '' }}>Semua status ({{ $statusCounts->sum() }})</option>
                    <option value="Pending" {{ $statusFilter === 'Pending' ? 'selected' : '' }}>Menunggu ({{ $statusCounts['Pending'] ?? 0 }})</option>
                    <option value="Resolved" {{ $statusFilter === 'Resolved' ? 'selected' : '' }}>Selesai ({{ $statusCounts['Resolved'] ?? 0 }})</option>
                    <option value="Rejected" {{ $statusFilter === 'Rejected' ? 'selected' : '' }}>Ditolak ({{ $statusCounts['Rejected'] ?? 0 }})</option>
                </select>
                <button type="submit" class="discussion-filter-apply">Terapkan</button>
            </form>
        </div>

        @if ($statusFilter !== 'all')
            <div class="discussion-active-filter">
                <span>Filter aktif: <strong>{{ $statusLabels[$statusFilter] ?? $statusFilter }}</strong></span>
                <a href="{{ route('discussions.index') }}">Tampilkan semua</a>
            </div>
        @endif

        @if (session('success'))
            <div class="discussion-alert" role="status">{{ session('success') }}</div>
        @endif

        <div class="discussion-table-wrapper">
            <table class="discussion-table">
                <thead>
                    <tr>
                        <th scope="col">ID</th>
                        <th scope="col">Judul laporan</th>
                        <th scope="col">Fasilitas</th>
                        <th scope="col">Kategori</th>
                        <th scope="col">Pelapor</th>
                        <th scope="col">Status</th>
                        <th scope="col">Pesan</th>
                        <th scope="col">Aktivitas terakhir</th>
                        <th scope="col">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($reports as $report)
                        @php
                            $statusClass = [
                                'Pending' => 'discussion-status--pending',
                                'Resolved' => 'discussion-status--resolved',
                                'Rejected' => 'discussion-status--rejected',
                            ][$report->status] ?? 'discussion-status--pending';
                        @endphp
                        <tr>
                            <td class="discussion-id">#{{ $report->id }}</td>
                            <td class="discussion-title-cell">
                                <span class="discussion-report-title">{{ $report->title }}</span>
                            </td>
                            <td class="discussion-strong-cell">{{ $report->facility?->name ?? '—' }}</td>
                            <td>{{ $report->category?->name ?? 'Tanpa kategori' }}</td>
                            <td>{{ $report->user?->name ?? 'Pengguna' }}</td>
                            <td>
                                <span class="discussion-status {{ $statusClass }}">
                                    {{ $statusLabels[$report->status] ?? $report->status }}
                                </span>
                            </td>
                            <td>
                                <span class="discussion-message-count {{ (int) $report->responses_count === 0 ? 'is-empty' : '' }}">
                                    {{ (int) $report->responses_count }}
                                </span>
                            </td>
                            <td class="discussion-date">
                                @php $activityAt = $report->responses_max_created_at ?: $report->updated_at; @endphp
                                {{ \Illuminate\Support\Carbon::parse($activityAt)->locale('id')->translatedFormat('d M Y, H:i') }}
                            </td>
                            <td>
                                <a class="discussion-open-button" href="{{ route('responses.show', [
                                    'uuid' => $report->identifier,
                                    'from' => 'discussions',
                                    'status' => $statusFilter,
                                    'page' => $reports->currentPage(),
                                ]) }}">
                                    Buka diskusi
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M5 12h14m-6-6 6 6-6 6" /></svg>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="discussion-table-empty">
                                <span class="discussion-empty-icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M20 11.5a7.5 7.5 0 0 1-7.5 7.5H7l-3 2v-6.5A7.5 7.5 0 1 1 20 11.5Z" /><path d="M8 10h8M8 13.5h5" /></svg>
                                </span>
                                <strong>{{ $statusFilter === 'all' ? 'Belum ada diskusi laporan' : 'Tidak ada laporan dengan status ini' }}</strong>
                                <span>{{ $statusFilter === 'all' ? 'Laporan yang dapat kamu akses akan muncul di sini.' : 'Coba pilih status lain atau tampilkan semua laporan.' }}</span>
                                @if ($statusFilter !== 'all')
                                    <a href="{{ route('discussions.index') }}" class="discussion-clear-filter">Tampilkan semua laporan</a>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="discussion-footer">
            <span>
                Menampilkan <strong>{{ $reports->total() === 0 ? 0 : (($reports->currentPage() - 1) * $reports->perPage()) + $reports->count() }}</strong>
                dari <strong>{{ $reports->total() }}</strong> laporan{{ $statusFilter !== 'all' ? ' yang difilter' : '' }}
            </span>
            <div class="discussion-pagination">
                <form method="GET" action="{{ route('discussions.index') }}">
                    @if ($statusFilter !== 'all')
                        <input type="hidden" name="status" value="{{ $statusFilter }}">
                    @endif
                    @if (!$reports->onFirstPage())
                        <button type="submit" name="page" value="{{ $reports->currentPage() - 1 }}" class="discussion-pagination-button">Sebelumnya</button>
                    @endif
                    @if ($reports->hasMorePages())
                        <button type="submit" name="page" value="{{ $reports->currentPage() + 1 }}" class="discussion-pagination-button">Selanjutnya</button>
                    @endif
                </form>
            </div>
        </div>
    </section>
</div>
@endsection
