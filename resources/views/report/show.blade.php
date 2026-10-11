@extends('layouts.app')

@push('styles')
    @vite('resources/css/report-show.css')
@endpush

@section('title', 'Detail Laporan')
@section('page-title', 'Detail Laporan')
@section('page-description', 'Ringkasan informasi dan status pengaduan fasilitas.')

@section('content')
@php
    $reportStatus = strtolower($report->status ?? 'pending');
    $reportSeverity = strtolower($report->severity ?? 'low');

    // Use related names when the controller loads them; otherwise fall back to foreign-key IDs.
    $categoryName = data_get($report, 'category.name')
        ?? data_get($report, 'categories.name');
    $facilityName = data_get($report, 'facility.name')
        ?? data_get($report, 'facilities.name');
    $reporterName = data_get($report, 'user.name')
        ?? data_get($report, 'reporter.name');
@endphp

<div class="report-show">
    <div class="report-show-toolbar">
        <a href="{{ url('/dashboard') }}" class="report-show-back">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path d="m15 18-6-6 6-6" />
            </svg>
            <span>Kembali ke Dashboard</span>
        </a>
        <span class="report-show-reference">Laporan #{{ $report->id }}</span>
    </div>

    <section class="report-show-hero" aria-labelledby="report-show-title">
        <div class="report-show-hero-copy">
            <div class="report-show-eyebrow">LAPORAN FASILITAS</div>
            <h2 id="report-show-title">{{ $report->title }}</h2>
            <p class="report-show-subtitle">
                Dibuat pada
                {{ $report->created_at ? \Illuminate\Support\Carbon::parse($report->created_at)->format('d M Y') : 'tanggal tidak tersedia' }}
            </p>
        </div>

        <div class="report-show-badges" aria-label="Status dan tingkat urgensi laporan">
            <span class="report-show-badge report-show-badge--status report-show-badge--{{ $reportStatus }}">
                <span class="report-show-badge-dot" aria-hidden="true"></span>
                {{ $report->status ?? 'Pending' }}
            </span>
            <span class="report-show-badge report-show-badge--severity report-show-badge--{{ $reportSeverity }}">
                Urgensi: {{ $report->severity ?? 'Low' }}
            </span>
        </div>
    </section>

    <div class="report-show-grid">
        <div class="report-show-main-column">
            <section class="report-show-card" aria-labelledby="reportOverviewTitle">
                <header class="report-show-card-header">
                    <span class="report-show-card-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="M7 3.75h7l4.25 4.5v12H7z" />
                            <path d="M14 3.75v4.5h4.25M9.5 12h6M9.5 15.5h6" />
                        </svg>
                    </span>
                    <div>
                        <h3 id="reportOverviewTitle">Informasi Laporan</h3>
                        <p>Informasi utama pengaduan yang dikirimkan.</p>
                    </div>
                </header>

                <div class="report-show-card-body">
                    <dl class="report-show-info-grid">
                        <div class="report-show-info-item">
                            <dt>ID Laporan</dt>
                            <dd class="report-show-mono">#{{ $report->id }}</dd>
                        </div>
                        <div class="report-show-info-item">
                            <dt>Kategori</dt>
                            <dd>
                                {{ $categoryName ?: ($report->categories_id ? 'Kategori #' . $report->categories_id : 'Belum ditentukan') }}
                            </dd>
                        </div>
                        <div class="report-show-info-item">
                            <dt>Fasilitas</dt>
                            <dd>
                                {{ $facilityName ?: ($report->facilities_id ? 'Fasilitas #' . $report->facilities_id : 'Belum ditentukan') }}
                            </dd>
                        </div>
                        <div class="report-show-info-item">
                            <dt>Pelapor</dt>
                            <dd>
                                {{ $reporterName ?: ($report->users_id ? 'Pengguna #' . $report->users_id : 'Tidak tersedia') }}
                            </dd>
                        </div>
                        <div class="report-show-info-item">
                            <dt>Tanggal dibuat</dt>
                            <dd>{{ $report->created_at ? \Illuminate\Support\Carbon::parse($report->created_at)->format('d M Y') : '—' }}</dd>
                        </div>
                        <div class="report-show-info-item">
                            <dt>Terakhir diperbarui</dt>
                            <dd>{{ $report->updated_at ? \Illuminate\Support\Carbon::parse($report->updated_at)->format('d M Y') : '—' }}</dd>
                        </div>
                    </dl>
                </div>
            </section>

            <section class="report-show-card" aria-labelledby="reportDescriptionTitle">
                <header class="report-show-card-header">
                    <span class="report-show-card-icon report-show-card-icon--description" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="M5 4.5h14v15H5z" />
                            <path d="M8 8h8M8 12h8M8 16h5" />
                        </svg>
                    </span>
                    <div>
                        <h3 id="reportDescriptionTitle">Deskripsi Masalah</h3>
                        <p>Penjelasan yang dicantumkan pada laporan.</p>
                    </div>
                </header>
                <div class="report-show-card-body">
                    @if (filled($report->description))
                        <p class="report-show-description">{{ $report->description }}</p>
                    @else
                        <div class="report-show-empty-description">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                                <circle cx="12" cy="12" r="9" />
                                <path d="M12 11v5m0-8h.01" />
                            </svg>
                            <span>Belum ada deskripsi untuk laporan ini.</span>
                        </div>
                    @endif
                </div>
            </section>
        </div>

        <aside class="report-show-side" aria-label="Ringkasan status laporan">
            <section class="report-show-card report-show-status-card" aria-labelledby="reportStatusTitle">
                <header class="report-show-card-header">
                    <span class="report-show-card-icon report-show-card-icon--status" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <circle cx="12" cy="12" r="8.5" />
                            <path d="M12 7v5l3.25 2" />
                        </svg>
                    </span>
                    <div>
                        <h3 id="reportStatusTitle">Status Penanganan</h3>
                        <p>Status laporan saat ini.</p>
                    </div>
                </header>
                <div class="report-show-card-body">
                    <div class="report-show-status-summary report-show-status-summary--{{ $reportStatus }}">
                        <span class="report-show-status-symbol" aria-hidden="true">
                            @if ($reportStatus === 'resolved')
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m5 12 4.5 4.5L19 7" /></svg>
                            @elseif ($reportStatus === 'rejected')
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m7 7 10 10M17 7 7 17" /></svg>
                            @else
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="8.5" /><path d="M12 7v5l3 2" /></svg>
                            @endif
                        </span>
                        <div>
                            <strong>{{ $report->status ?? 'Pending' }}</strong>
                            <p>
                                @if ($reportStatus === 'resolved')
                                    Laporan ini telah diselesaikan.
                                @elseif ($reportStatus === 'rejected')
                                    Laporan ini ditolak.
                                @else
                                    Laporan menunggu tindak lanjut.
                                @endif
                            </p>
                        </div>
                    </div>

                    <div class="report-show-side-metadata">
                        <div class="report-show-side-metadata-row">
                            <span>Tingkat urgensi</span>
                            <strong class="report-show-severity-text report-show-severity-text--{{ $reportSeverity }}">{{ $report->severity ?? 'Low' }}</strong>
                        </div>
                        <div class="report-show-side-metadata-row">
                            <span>Jumlah perubahan</span>
                            <strong>{{ $report->edit_count ?? 0 }} kali</strong>
                        </div>
                    </div>
                </div>
            </section>

            <section class="report-show-help-card" aria-labelledby="reportHelpTitle">
                <span class="report-show-help-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M12 3 21 7.5v9L12 21l-9-4.5v-9z" />
                        <path d="m8 12 2.5 2.5L16 9" />
                    </svg>
                </span>
                <h3 id="reportHelpTitle">Informasi</h3>
                <p>Gunakan nomor laporan <strong>#{{ $report->id }}</strong> jika kamu perlu menanyakan perkembangan pengaduan ini kepada petugas.</p>
                <a href="{{ url('/dashboard') }}" class="report-show-secondary-link">
                    Kembali ke dashboard
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M5 12h14m-6-6 6 6-6 6" /></svg>
                </a>
            </section>
        </aside>
    </div>
</div>
@endsection
