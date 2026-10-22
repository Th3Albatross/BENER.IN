@extends('layouts.app')

@section('title', 'Web Analytics')
@section('page-title', 'Web Analytics')
@section('page-description', 'Ringkasan pola pengaduan dan penanganan fasilitas sekolah.')

@push('styles')
    @vite('resources/css/analytics.css')
@endpush

@push('scripts')
    @vite('resources/js/analytics.js')
@endpush

@section('content')
@php
    $filters = $filters ?? [
        'period' => '30d',
        'start_date' => '',
        'end_date' => '',
        'label' => '30 hari terakhir',
    ];

    $hasAnalyticsPayload = isset($summary) && array_key_exists('total', $summary);
    $summary = $summary ?? [];
    $charts = $charts ?? [];
    $insights = $insights ?? [];
    $range = $range ?? [];
    $hasReports = $hasAnalyticsPayload && (int) ($summary['total'] ?? 0) > 0;
    $totalReports = (int) ($summary['total'] ?? 0);

    // Category and facility rankings are capped so long tails stay readable.
    $rankingLimit = 8;
    $categoryRows = $charts['categories'] ?? [];
    $facilityRows = $charts['facilities'] ?? [];

    $chartPayload = [
        'trend' => $charts['trend'] ?? [],
        'statuses' => $charts['statuses'] ?? [],
        'severities' => $charts['severities'] ?? [],
        'categories' => array_slice($categoryRows, 0, $rankingLimit),
        'facilities' => array_slice($facilityRows, 0, $rankingLimit),
    ];

    // Human-readable date span shown under the period label.
    $rangeText = null;
    if (!empty($range['start_date']) && !empty($range['end_date'])) {
        $rangeStart = \Carbon\Carbon::parse($range['start_date'])->locale('id');
        $rangeEnd = \Carbon\Carbon::parse($range['end_date'])->locale('id');
        $rangeText = $rangeStart->translatedFormat('j M Y') . ' – ' . $rangeEnd->translatedFormat('j M Y');
    }

    // Status legend rows: label, css modifier, count and share of the total.
    $statusLabels = ['Pending' => 'Menunggu', 'Resolved' => 'Selesai', 'Rejected' => 'Ditolak'];
    $statusRows = collect($charts['statuses'] ?? [])->map(fn ($row) => [
        'label' => $statusLabels[$row['label']] ?? $row['label'],
        'key' => strtolower($row['label']),
        'total' => (int) $row['total'],
        'percent' => $totalReports > 0 ? round(((int) $row['total'] / $totalReports) * 100) : 0,
    ]);

    // Period-over-period change for the total card.
    $previousTotal = $insights['previous_total'] ?? null;
    $changePercent = $insights['change_percent'] ?? null;
    $resolutionRate = $insights['resolution_rate'] ?? null;
    $dailyAverage = $insights['daily_average'] ?? null;
    $urgentOpen = (int) ($insights['urgent_open'] ?? 0);
    $topFacility = $facilityRows[0] ?? null;
    $fmt = fn ($value, int $decimals = 0) => number_format((float) $value, $decimals, ',', '.');
@endphp

<div class="analytics-page">
    <header class="analytics-topbar">
        <button type="button" class="analytics-export" data-analytics-print title="Buka dialog cetak, lalu pilih Simpan sebagai PDF">
            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 3v11m0 0 4-4m-4 4-4-4M5 16v4h14v-4"/></svg>
            <span>Ekspor PDF</span>
        </button>
    </header>

    <section class="analytics-toolbar" aria-label="Filter data analytics">
        <div class="analytics-toolbar__context">
            <span class="analytics-context-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none"><rect x="3.5" y="5" width="17" height="15" rx="2"/><path d="M7.5 3.5v3M16.5 3.5v3M3.5 9.5h17"/></svg>
            </span>
            <div>
                <strong>{{ $filters['label'] ?? 'Periode terpilih' }}</strong>
                @if ($rangeText)
                    <small>{{ $rangeText }}</small>
                @endif
            </div>
        </div>

        <form action="{{ url('/analytics') }}" method="GET" class="analytics-filter-form" data-analytics-filter data-period="{{ $filters['period'] ?? '30d' }}">
            <label class="analytics-sr-only" for="analytics-period">Periode</label>
            <select name="period" id="analytics-period">
                <option value="7d" {{ ($filters['period'] ?? '30d') === '7d' ? 'selected' : '' }}>7 hari</option>
                <option value="30d" {{ ($filters['period'] ?? '30d') === '30d' ? 'selected' : '' }}>30 hari</option>
                <option value="90d" {{ ($filters['period'] ?? '30d') === '90d' ? 'selected' : '' }}>90 hari</option>
                <option value="all" {{ ($filters['period'] ?? '30d') === 'all' ? 'selected' : '' }}>Sepanjang waktu</option>
                <option value="custom" {{ ($filters['period'] ?? '30d') === 'custom' ? 'selected' : '' }}>Tanggal khusus</option>
            </select>
            <label class="analytics-date-field analytics-custom-only" for="analytics-start-date">
                <span>Dari</span>
                <input type="date" id="analytics-start-date" name="start_date" max="{{ now()->toDateString() }}" value="{{ $filters['start_date'] ?? '' }}" aria-label="Dari tanggal">
            </label>
            <span class="analytics-date-separator analytics-custom-only" aria-hidden="true">—</span>
            <label class="analytics-date-field analytics-custom-only" for="analytics-end-date">
                <span>Sampai</span>
                <input type="date" id="analytics-end-date" name="end_date" max="{{ now()->toDateString() }}" value="{{ $filters['end_date'] ?? '' }}" aria-label="Sampai tanggal">
            </label>
            <button type="submit" class="analytics-apply" aria-label="Terapkan filter">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 6h16M7 12h10m-7 6h4"/></svg>
                <span>Terapkan</span>
            </button>
            <a class="analytics-reset" href="{{ url('/analytics') }}">Reset</a>
            @error('period')<span class="analytics-field-error" role="alert">{{ $message }}</span>@enderror
            @error('start_date')<span class="analytics-field-error" role="alert">{{ $message }}</span>@enderror
            @error('end_date')<span class="analytics-field-error" role="alert">{{ $message }}</span>@enderror
        </form>
    </section>

    @if ($hasReports)
        <div class="analytics-chart-notice" role="alert">
            <strong>Grafik belum dapat dimuat.</strong>
            Pastikan Vite berjalan (<code>npm run dev</code>) atau jalankan <code>npm run build</code>, lalu muat ulang halaman.
            Jika masih gagal, buka Console browser (F12) untuk melihat pesan galatnya.
        </div>
    @endif

    <section class="analytics-metrics" aria-label="Ringkasan pengaduan">
        <article class="analytics-metric analytics-metric--total">
            <span class="analytics-metric__label">Total pengaduan <span class="analytics-info" title="Jumlah pengaduan yang dibuat pada periode terpilih">i</span></span>
            <strong>{{ $hasAnalyticsPayload ? number_format((int) $summary['total'], 0, ',', '.') : '—' }}</strong>
            @if ($changePercent !== null)
                @php $direction = $changePercent > 0 ? 'up' : ($changePercent < 0 ? 'down' : 'flat'); @endphp
                <span class="analytics-delta analytics-delta--{{ $direction }}" title="Dibandingkan {{ $fmt($previousTotal) }} pengaduan pada periode sebelumnya dengan jumlah hari yang sama">
                    <span aria-hidden="true">{{ $direction === 'up' ? '↑' : ($direction === 'down' ? '↓' : '→') }}</span>
                    {{ $fmt(abs($changePercent), 1) }}%
                    <em>vs periode sebelumnya</em>
                </span>
            @elseif ($previousTotal === 0 && $totalReports > 0)
                <span class="analytics-delta analytics-delta--flat" title="Tidak ada pengaduan pada periode sebelumnya, jadi persentase perubahan tidak dapat dihitung">
                    <em>Periode sebelumnya: 0</em>
                </span>
            @endif
        </article>
        <article class="analytics-metric analytics-metric--pending">
            <span class="analytics-metric__label"><span class="analytics-status-dot"></span>Menunggu</span>
            <strong>{{ $hasAnalyticsPayload ? number_format((int) ($summary['pending'] ?? 0), 0, ',', '.') : '—' }}</strong>
        </article>
        <article class="analytics-metric analytics-metric--resolved">
            <span class="analytics-metric__label"><span class="analytics-status-dot"></span>Selesai</span>
            <strong>{{ $hasAnalyticsPayload ? number_format((int) ($summary['resolved'] ?? 0), 0, ',', '.') : '—' }}</strong>
        </article>
        <article class="analytics-metric analytics-metric--rejected">
            <span class="analytics-metric__label"><span class="analytics-status-dot"></span>Ditolak</span>
            <strong>{{ $hasAnalyticsPayload ? number_format((int) ($summary['rejected'] ?? 0), 0, ',', '.') : '—' }}</strong>
        </article>
    </section>

    @if ($hasReports)
        <section class="analytics-insights" aria-label="Indikator penanganan">
            <article class="analytics-insight">
                <span class="analytics-insight__label">Rasio selesai</span>
                <strong>{{ $resolutionRate !== null ? $fmt($resolutionRate, 1) . '%' : '—' }}</strong>
                <span class="analytics-progress" role="img" aria-label="{{ $resolutionRate !== null ? $fmt($resolutionRate, 1) . ' persen laporan selesai' : 'Belum ada data' }}">
                    <i style="width: {{ min(100, max(0, (float) ($resolutionRate ?? 0))) }}%"></i>
                </span>
                <span class="analytics-insight__note">{{ $fmt($summary['resolved'] ?? 0) }} dari {{ $fmt($totalReports) }} laporan sudah selesai</span>
            </article>

            <article class="analytics-insight {{ $urgentOpen > 0 ? 'analytics-insight--alert' : '' }}">
                <span class="analytics-insight__label">Perlu perhatian</span>
                <strong>{{ $fmt($urgentOpen) }}</strong>
                <span class="analytics-insight__note">Masih menunggu dengan keparahan tinggi atau kritis</span>
            </article>

            <article class="analytics-insight">
                <span class="analytics-insight__label">Rata-rata per hari</span>
                <strong>{{ $dailyAverage !== null ? $fmt($dailyAverage, 1) : '—' }}</strong>
                <span class="analytics-insight__note">Pengaduan baru setiap hari pada periode ini</span>
            </article>

            <article class="analytics-insight">
                <span class="analytics-insight__label">Fasilitas teratas</span>
                <strong class="analytics-insight__text" title="{{ $topFacility['label'] ?? '' }}">{{ $topFacility['label'] ?? '—' }}</strong>
                <span class="analytics-insight__note">
                    @if ($topFacility)
                        {{ $fmt($topFacility['total']) }} laporan · {{ $fmt(round(($topFacility['total'] / max(1, $totalReports)) * 100)) }}% dari total
                    @else
                        Belum ada data
                    @endif
                </span>
            </article>
        </section>
    @endif

    <section class="analytics-section analytics-trend-section" aria-labelledby="analytics-trend-title">
        <div class="analytics-section-heading">
            <div>
                <h3 id="analytics-trend-title">Tren pengaduan</h3>
            </div>
            @if ($hasReports)
                <span class="analytics-subtle-tag" data-analytics-trend-tag></span>
            @endif
        </div>
        @if ($hasReports)
            <div class="analytics-trend-canvas"><canvas id="analytics-trend-chart" role="img" aria-label="Grafik tren jumlah pengaduan"></canvas></div>
        @else
            <div class="analytics-empty-state analytics-empty-state--trend">
                <span class="analytics-empty-state__icon" aria-hidden="true">⌁</span>
                <strong>{{ $hasAnalyticsPayload ? 'Belum ada pengaduan pada periode ini' : 'Data analytics belum tersedia' }}</strong>
                <span>{{ $hasAnalyticsPayload ? 'Coba pilih rentang tanggal yang lain.' : 'Grafik akan tampil saat dataset dari controller sudah tersedia.' }}</span>
            </div>
        @endif
    </section>

    <div class="analytics-lower-grid">
        <section class="analytics-section analytics-lower-panel analytics-status-panel" aria-labelledby="analytics-status-title">
            <div class="analytics-section-heading">
                <div>
                    <h3 id="analytics-status-title">Status laporan</h3>
                </div>
            </div>
            @if ($hasReports)
                <div class="analytics-status-content">
                    <div class="analytics-status-canvas"><canvas id="analytics-status-chart" role="img" aria-label="Grafik distribusi status laporan"></canvas></div>
                    <ul class="analytics-status-legend">
                        @foreach ($statusRows as $row)
                            <li>
                                <i class="analytics-legend-dot analytics-legend-dot--{{ $row['key'] }}"></i>
                                <span class="analytics-status-legend__name">{{ $row['label'] }}</span>
                                <span class="analytics-status-legend__value">{{ $fmt($row['total']) }} <small>{{ $row['percent'] }}%</small></span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @else
                <div class="analytics-empty-state analytics-empty-state--small"><strong>{{ $hasAnalyticsPayload ? 'Belum ada data' : 'Data belum tersedia' }}</strong></div>
            @endif
        </section>

        <section class="analytics-section analytics-lower-panel" aria-labelledby="analytics-category-title">
            <div class="analytics-section-heading">
                <div>
                    <h3 id="analytics-category-title">Kategori pengaduan</h3>
                </div>
                @if (count($categoryRows) > $rankingLimit)
                    <span class="analytics-subtle-tag">{{ $rankingLimit }} teratas dari {{ count($categoryRows) }}</span>
                @endif
            </div>
            @if ($hasReports)
                <div class="analytics-bar-canvas"><canvas id="analytics-category-chart" role="img" aria-label="Grafik jumlah laporan per kategori"></canvas></div>
            @else
                <div class="analytics-empty-state analytics-empty-state--small"><strong>{{ $hasAnalyticsPayload ? 'Belum ada data' : 'Data belum tersedia' }}</strong></div>
            @endif
        </section>

        <section class="analytics-section analytics-lower-panel" aria-labelledby="analytics-severity-title">
            <div class="analytics-section-heading">
                <div>
                    <h3 id="analytics-severity-title">Tingkat keparahan</h3>
                </div>
            </div>
            @if ($hasReports)
                <div class="analytics-bar-canvas"><canvas id="analytics-severity-chart" role="img" aria-label="Grafik tingkat keparahan laporan"></canvas></div>
            @else
                <div class="analytics-empty-state analytics-empty-state--small"><strong>{{ $hasAnalyticsPayload ? 'Belum ada data' : 'Data belum tersedia' }}</strong></div>
            @endif
        </section>

        <section class="analytics-section analytics-lower-panel" aria-labelledby="analytics-facility-title">
            <div class="analytics-section-heading">
                <div>
                    <h3 id="analytics-facility-title">Fasilitas</h3>
                </div>
                @if (count($facilityRows) > $rankingLimit)
                    <span class="analytics-subtle-tag">{{ $rankingLimit }} teratas dari {{ count($facilityRows) }}</span>
                @endif
            </div>
            @if ($hasReports)
                <div class="analytics-bar-canvas"><canvas id="analytics-facility-chart" role="img" aria-label="Grafik jumlah laporan per fasilitas"></canvas></div>
            @else
                <div class="analytics-empty-state analytics-empty-state--small"><strong>{{ $hasAnalyticsPayload ? 'Belum ada data' : 'Data belum tersedia' }}</strong></div>
            @endif
        </section>
    </div>

    <aside class="analytics-footnote" aria-label="Catatan perhitungan">
        <span class="analytics-footnote__icon" aria-hidden="true">i</span>
        <p>Statistik menggunakan tanggal laporan dibuat dan status yang tersimpan saat ini. Durasi penyelesaian tidak ditampilkan karena belum ada timestamp historis yang memastikan waktu perubahan status menjadi selesai.</p>
    </aside>

    @if ($hasReports)
        {{-- If the Vite script never ran (not built, not served, import failed), say so instead of leaving blank panels. --}}
        <script>
            window.addEventListener('load', () => {
                setTimeout(() => {
                    const page = document.querySelector('.analytics-page');
                    if (page && page.querySelector('canvas') && !page.dataset.chartsReady) {
                        page.classList.add('analytics-page--charts-failed');
                    }
                }, 1500);
            });
        </script>
    @endif

    <script type="application/json" id="analytics-chart-data">@json($chartPayload, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)</script>
</div>
@endsection
