@extends('layouts.app')

@push('styles')
    @vite('resources/css/discussion-index.css')
@endpush

@section('title', 'Diskusi Laporan')
@section('page-title', 'Diskusi Laporan')
@section('page-description', 'Lihat percakapan dan pantau laporan berdasarkan statusnya.')

@section('content')
@php
    $reports = $_reports;
    $statusFilter = $_statusFilter ?? 'all';
    $statusLabels = [
        'Pending' => 'Menunggu',
        'Resolved' => 'Disetujui / Selesai',
        'Rejected' => 'Ditolak',
    ];
@endphp

<div class="discussion-index-page">
    <div class="discussion-index-toolbar">
        <form method="GET" action="{{ route('discussions.index') }}" class="discussion-index-filter">
            <label for="discussion-status-filter">Filter status</label>
            <select name="status" id="discussion-status-filter" class="discussion-index-filter-select">
                <option value="all" {{ $statusFilter === 'all' ? 'selected' : '' }}>Semua status ({{ $_statusCounts->sum() }})</option>
                <option value="Pending" {{ $statusFilter === 'Pending' ? 'selected' : '' }}>Menunggu ({{ $_statusCounts['Pending'] ?? 0 }})</option>
                <option value="Resolved" {{ $statusFilter === 'Resolved' ? 'selected' : '' }}>Disetujui / Selesai ({{ $_statusCounts['Resolved'] ?? 0 }})</option>
                <option value="Rejected" {{ $statusFilter === 'Rejected' ? 'selected' : '' }}>Ditolak ({{ $_statusCounts['Rejected'] ?? 0 }})</option>
            </select>
            <button type="submit" class="discussion-index-filter-button">Terapkan</button>
        </form>

        <span class="discussion-index-total">
            {{ $reports->total() }} laporan
        </span>
    </div>

    @if ($statusFilter !== 'all')
        <div class="discussion-index-active-filter">
            <span>Filter aktif: <strong>{{ $statusLabels[$statusFilter] ?? $statusFilter }}</strong></span>
            <a href="{{ route('discussions.index') }}">Tampilkan semua</a>
        </div>
    @endif

    @if (session('success'))
        <div class="discussion-index-alert discussion-index-alert--success" role="status">
            {{ session('success') }}
        </div>
    @endif

    <section class="discussion-index-list" aria-label="Daftar diskusi laporan">
        @forelse ($reports as $report)
            @php
                $statusClass = [
                    'Pending' => 'discussion-index-status--pending',
                    'Resolved' => 'discussion-index-status--resolved',
                    'Rejected' => 'discussion-index-status--rejected',
                ][$report->status] ?? 'discussion-index-status--pending';
            @endphp
            <article class="discussion-index-card">
                <div class="discussion-index-card-main">
                    <div class="discussion-index-card-title-row">
                        <h2>{{ $report->title }}</h2>
                        <span class="discussion-index-status {{ $statusClass }}">
                            {{ $statusLabels[$report->status] ?? $report->status }}
                        </span>
                    </div>

                    <div class="discussion-index-meta">
                        <span>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M3 21h18M5 21V5l7-2 7 2v16M9 8h1m4 0h1M9 12h1m4 0h1M9 16h1m4 0h1" /></svg>
                            {{ $report->facilities_name }}
                        </span>
                        <span>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M4 5h16v14H4z" /><path d="M8 9h8M8 13h5" /></svg>
                            {{ $report->categories_name ?: 'Tanpa kategori' }}
                        </span>
                        <span>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><circle cx="12" cy="8" r="4" /><path d="M4 21a8 8 0 0 1 16 0" /></svg>
                            {{ $report->user_name }}
                        </span>
                    </div>

                    <p class="discussion-index-description">
                        {{ \Illuminate\Support\Str::limit($report->description ?: 'Tidak ada deskripsi tambahan.', 150) }}
                    </p>
                </div>

                <div class="discussion-index-card-footer">
                    <div class="discussion-index-activity">
                        <span class="discussion-index-message-count {{ (int) $report->responses_count === 0 ? 'is-empty' : '' }}">
                            {{ (int) $report->responses_count === 0 ? 'Belum ada pesan' : $report->responses_count . ' pesan' }}
                        </span>
                        <span>
                            Aktivitas terakhir:
                            @if ($report->last_response_at)
                                {{ \Illuminate\Support\Carbon::parse($report->last_response_at)->locale('id')->translatedFormat('d M Y, H:i') }}
                            @else
                                {{ \Illuminate\Support\Carbon::parse($report->updated_at)->locale('id')->translatedFormat('d M Y, H:i') }}
                            @endif
                        </span>
                    </div>
                    <a class="discussion-index-open-button" href="{{ route('responses.show', ['uuid' => $report->identifier]) }}">
                        Buka Diskusi
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M5 12h14m-6-6 6 6-6 6" /></svg>
                    </a>
                </div>
            </article>
        @empty
            <div class="discussion-index-empty">
                <span class="discussion-index-empty-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M20 11.5a7.5 7.5 0 0 1-7.5 7.5H7l-3 2v-6.5A7.5 7.5 0 1 1 20 11.5Z" /><path d="M8 10h8M8 13.5h5" /></svg>
                </span>
                <h2>Tidak ada laporan untuk filter ini</h2>
                <p>
                    @if ($statusFilter === 'all')
                        Laporan yang bisa kamu akses akan muncul di sini.
                    @else
                        Tidak ditemukan laporan dengan status {{ strtolower($statusLabels[$statusFilter] ?? $statusFilter) }}.
                    @endif
                </p>
                @if ($statusFilter !== 'all')
                    <a href="{{ route('discussions.index') }}" class="discussion-index-clear-filter">Tampilkan semua laporan</a>
                @endif
            </div>
        @endforelse
    </section>

    <div class="reports-footer discussion-index-footer">
        <span>
            Menampilkan
            <strong>{{ $reports->firstItem() ?? 0 }}–{{ $reports->lastItem() ?? 0 }}</strong>
            dari <strong>{{ $reports->total() }}</strong> laporan
        </span>
        <div class="pagination">
            <form method="GET" action="{{ route('discussions.index') }}">
                @if ($statusFilter !== 'all')
                    <input type="hidden" name="status" value="{{ $statusFilter }}">
                @endif

                @if (!$reports->onFirstPage())
                    <button type="submit" name="page" value="{{ $reports->currentPage() - 1 }}" class="pagination-button">Sebelumnya</button>
                @endif

                @if ($reports->hasMorePages())
                    <button type="submit" name="page" value="{{ $reports->currentPage() + 1 }}" class="pagination-button">Selanjutnya</button>
                @endif
            </form>
        </div>
    </div>
</div>
@endsection
