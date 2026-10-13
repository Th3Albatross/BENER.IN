@extends('layouts.app')

@push('styles')
    @vite('resources/css/report-show.css')
@endpush

@section('title', 'Detail Laporan')
@section('page-title', 'Detail Laporan')
@section('page-description', 'Informasi, bukti foto, dan diskusi laporan fasilitas.')

@section('content')
@php
    $reportStatus = strtolower($report->status ?? 'pending');
    $reportSeverity = strtolower($report->severity ?? 'low');

    // Relationship names are checked so this view stays safe if a relationship
    // has not been defined yet. The controller should eager-load these relations.
    $category = method_exists($report, 'category') ? $report->category : null;
    $facility = method_exists($report, 'facility') ? $report->facility : null;
    $reporter = method_exists($report, 'user') ? $report->user : null;

    $categoryName = data_get($category, 'name')
        ?: data_get($category, 'categories_name')
        ?: data_get($category, 'title');
    $facilityName = data_get($facility, 'name')
        ?: data_get($facility, 'facilities_name')
        ?: data_get($facility, 'title');
    $reporterName = data_get($reporter, 'name')
        ?: data_get($reporter, 'username');

    $photos = collect($photos ?? []);
    $responses = isset($responses)
        ? collect($responses)
        : (method_exists($report, 'responses') ? collect($report->responses) : collect());

    $reportIdentifier = $report->identifier ?? null;
@endphp

<div class="report-show">
    <div class="report-show-toolbar">
        <a href="{{ url('/dashboard') }}" class="report-show-back">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path d="m15 18-6-6 6-6" />
            </svg>
            <span>Kembali ke Dashboard</span>
        </a>
        @if ($reportIdentifier)
            <span class="report-show-reference" title="Kode referensi laporan">{{ $reportIdentifier }}</span>
        @endif
    </div>

    @if (session('success'))
        <div class="report-show-alert report-show-alert--success" role="status">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="report-show-alert report-show-alert--error" role="alert">{{ session('error') }}</div>
    @endif

    <section class="report-show-hero" aria-labelledby="report-show-title">
        <div class="report-show-hero-copy">
            <div class="report-show-eyebrow">LAPORAN FASILITAS</div>
            <h2 id="report-show-title">{{ $report->title }}</h2>
            <p class="report-show-subtitle">
                Dibuat pada
                {{ $report->created_at ? \Illuminate\Support\Carbon::parse($report->created_at)->format('d M Y, H:i') : 'tanggal tidak tersedia' }}
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
                        <p>Detail utama pengaduan fasilitas.</p>
                    </div>
                </header>

                <div class="report-show-card-body">
                    <dl class="report-show-info-grid">
                        <div class="report-show-info-item report-show-info-item--wide">
                            <dt>Kode Laporan (UUID)</dt>
                            <dd class="report-show-mono">{{ $reportIdentifier ?: 'Tidak tersedia' }}</dd>
                        </div>
                        <div class="report-show-info-item">
                            <dt>Kategori</dt>
                            <dd>{{ $categoryName ?: 'Belum ditentukan' }}</dd>
                        </div>
                        <div class="report-show-info-item">
                            <dt>Fasilitas</dt>
                            <dd>{{ $facilityName ?: 'Informasi fasilitas tidak tersedia' }}</dd>
                        </div>
                        <div class="report-show-info-item">
                            <dt>Pelapor</dt>
                            <dd>{{ $reporterName ?: 'Informasi pelapor tidak tersedia' }}</dd>
                        </div>
                        <div class="report-show-info-item">
                            <dt>Lokasi / Ruangan</dt>
                            <dd>{{ $report->location_description ?: 'Tidak dicantumkan' }}</dd>
                        </div>
                        <div class="report-show-info-item">
                            <dt>Tanggal Kejadian</dt>
                            <dd>{{ $report->incident_date ? \Illuminate\Support\Carbon::parse($report->incident_date)->format('d M Y') : '—' }}</dd>
                        </div>
                        <div class="report-show-info-item">
                            <dt>Tanggal dibuat</dt>
                            <dd>{{ $report->created_at ? \Illuminate\Support\Carbon::parse($report->created_at)->format('d M Y, H:i') : '—' }}</dd>
                        </div>
                        <div class="report-show-info-item">
                            <dt>Terakhir diperbarui</dt>
                            <dd>{{ $report->updated_at ? \Illuminate\Support\Carbon::parse($report->updated_at)->format('d M Y, H:i') : '—' }}</dd>
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
                        <p>Penjelasan dari pelapor.</p>
                    </div>
                </header>
                <div class="report-show-card-body">
                    @if (filled($report->description))
                        <p class="report-show-description">{{ $report->description }}</p>
                    @else
                        <div class="report-show-empty-state">Belum ada deskripsi untuk laporan ini.</div>
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
                <p>Simpan kode referensi laporan ini jika kamu perlu menanyakan perkembangan pengaduan kepada petugas.</p>
                <a href="{{ url('/dashboard') }}" class="report-show-secondary-link">
                    Kembali ke dashboard
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M5 12h14m-6-6 6 6-6 6" /></svg>
                </a>
            </section>
        </aside>
    </div>

    {{-- Foto dikirim sebagai nama file atau objek berisi path/filename/name/url dari controller. --}}
    <section class="report-show-card" aria-labelledby="reportPhotosTitle">
        <header class="report-show-card-header">
            <span class="report-show-card-icon report-show-card-icon--photos" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <rect x="3.5" y="4.5" width="17" height="15" rx="2" />
                    <circle cx="8.5" cy="9" r="1.5" />
                    <path d="m5 17 5-5 3 3 2.5-2.5L19 16" />
                </svg>
            </span>
            <div>
                <h3 id="reportPhotosTitle">Bukti Foto</h3>
                <p>{{ $photos->count() }} foto bukti terlampir pada laporan ini.</p>
            </div>
        </header>
        <div class="report-show-card-body">
            @if ($photos->isNotEmpty())
                <div class="report-show-photo-grid">
                    @foreach ($photos as $photo)
                        @php
                            $photoPath = is_string($photo)
                                ? $photo
                                : (data_get($photo, 'path') ?? data_get($photo, 'filename') ?? data_get($photo, 'name') ?? '');
                            $photoName = basename((string) $photoPath);
                            $photoUrl = data_get($photo, 'url')
                                ?: ($photoName !== '' ? url('/reports/' . rawurlencode((string) $reportIdentifier) . '/photos/' . rawurlencode($photoName)) : null);
                        @endphp
                        @if ($photoUrl)
                            <button
                                type="button"
                                class="report-show-photo"
                                data-photo-src="{{ $photoUrl }}"
                                data-photo-alt="Bukti foto {{ $loop->iteration }} untuk laporan {{ $reportIdentifier }}"
                                aria-label="Perbesar bukti foto {{ $loop->iteration }}">
                                <img src="{{ $photoUrl }}" alt="Bukti foto {{ $loop->iteration }}" loading="lazy" decoding="async" referrerpolicy="no-referrer">
                                <span class="report-show-photo-overlay"><span>Lihat foto</span></span>
                            </button>
                        @endif
                    @endforeach
                </div>
            @else
                <div class="report-show-empty-state report-show-empty-state--illustrated">
                    <span class="report-show-empty-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><rect x="3.5" y="4.5" width="17" height="15" rx="2" /><path d="m5 17 5-5 3 3 2.5-2.5L19 16" /></svg>
                    </span>
                    <strong>Belum ada bukti foto</strong>
                    <span>Foto bukti yang terlampir pada laporan akan ditampilkan di sini.</span>
                </div>
            @endif
        </div>
    </section>

    {{-- Diskusi berada paling bawah; frame history memiliki scroll internal. --}}
    <section class="report-show-card report-show-discussion" aria-labelledby="reportDiscussionTitle">
        <header class="report-show-card-header">
            <span class="report-show-card-icon report-show-card-icon--discussion" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path d="M20 11.5a7.5 7.5 0 0 1-7.5 7.5H7l-3 2v-6.5A7.5 7.5 0 1 1 20 11.5Z" />
                    <path d="M8 10h8M8 13.5h5" />
                </svg>
            </span>
            <div>
                <h3 id="reportDiscussionTitle">Diskusi Laporan</h3>
                <p>Percakapan antara pelapor dan petugas terkait laporan ini.</p>
            </div>
            <span class="report-show-discussion-count">{{ $responses->count() }} pesan</span>
        </header>

        <div class="report-show-discussion-body">
            <div class="report-show-chat-frame" id="reportChatFrame" aria-label="Riwayat diskusi" aria-live="polite">
                @forelse ($responses as $response)
                    @php
                        $responseUser = method_exists($response, 'user') ? $response->user : data_get($response, 'user');
                        $isOwnResponse = auth()->check() && (int) data_get($response, 'users_id') === (int) auth()->id();
                        $senderName = data_get($responseUser, 'name') ?: data_get($responseUser, 'username') ?: ($isOwnResponse ? (auth()->user()->name ?? 'Kamu') : 'Pengguna');
                        $senderRole = data_get($responseUser, 'role') ?: ($isOwnResponse ? (auth()->user()->role ?? 'Pengguna') : '');
                        $senderRoleLabel = $senderRole !== '' ? ucfirst(str_replace('_', ' ', strtolower((string) $senderRole))) : 'Pengguna';
                        $responseDate = data_get($response, 'created_at');
                    @endphp
                    <article class="report-show-message {{ $isOwnResponse ? 'report-show-message--mine' : '' }}" data-response-id="{{ $response->id }}">
                        <div class="report-show-message-avatar" aria-hidden="true">{{ strtoupper(mb_substr((string) $senderName, 0, 1)) }}</div>
                        <div class="report-show-message-content">
                            <div class="report-show-message-meta">
                                <strong>{{ $senderName }}</strong>
                                <span class="report-show-role">{{ $senderRoleLabel }}</span>
                                @if ($responseDate)
                                    <time datetime="{{ \Illuminate\Support\Carbon::parse($responseDate)->toIso8601String() }}">{{ \Illuminate\Support\Carbon::parse($responseDate)->format('d M Y, H:i') }}</time>
                                @endif
                            </div>
                            <div class="report-show-message-bubble">
                                <div class="report-show-message-text">{{ $response->message }}</div>
                                @if ($isOwnResponse)
                                    <button type="button" class="report-show-edit-trigger" aria-expanded="false">Edit</button>
                                @endif
                            </div>

                            @if ($isOwnResponse)
                                <form
                                    class="report-show-edit-form"
                                    action="{{ url('/reports/' . rawurlencode((string) $reportIdentifier) . '/responses/' . $response->id) }}"
                                    method="POST"
                                    hidden>
                                    @csrf
                                    @method('PATCH')
                                    <label class="report-show-sr-only" for="response-message-{{ $response->id }}">Edit pesan</label>
                                    <textarea id="response-message-{{ $response->id }}" name="message" rows="3" maxlength="5000" required>{{ $response->message }}</textarea>
                                    <div class="report-show-edit-actions">
                                        <button type="button" class="report-show-button report-show-button--quiet report-show-edit-cancel">Batal</button>
                                        <button type="submit" class="report-show-button report-show-button--primary">Simpan perubahan</button>
                                    </div>
                                </form>
                            @endif
                        </div>
                    </article>
                @empty
                    <div class="report-show-empty-state report-show-chat-empty" id="reportChatEmpty">
                        <span class="report-show-empty-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M20 11.5a7.5 7.5 0 0 1-7.5 7.5H7l-3 2v-6.5A7.5 7.5 0 1 1 20 11.5Z" /><path d="M8 10h8M8 13.5h5" /></svg>
                        </span>
                        <strong>Belum ada diskusi</strong>
                        <span>Pesan dari pelapor dan petugas akan muncul di area ini.</span>
                    </div>
                @endforelse
            </div>

            <form class="report-show-composer" action="{{ url('/reports/' . rawurlencode((string) $reportIdentifier) . '/responses') }}" method="POST">
                @csrf
                <label for="new-response-message">Tulis pesan</label>
                <textarea id="new-response-message" name="message" rows="3" maxlength="5000" placeholder="Tulis pesan untuk pelapor atau petugas..." required>{{ old('message') }}</textarea>
                @error('message')
                    <p class="report-show-field-error" role="alert">{{ $message }}</p>
                @enderror
                <div class="report-show-composer-footer">
                    <p>Gunakan bahasa yang sopan dan jelaskan informasi yang relevan dengan laporan.</p>
                    <button type="submit" class="report-show-button report-show-button--primary">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m21 3-7.5 18-3.5-7-7-3.5z" /><path d="M10 14 21 3" /></svg>
                        Kirim pesan
                    </button>
                </div>
            </form>
        </div>
    </section>
</div>

<div class="report-show-lightbox" id="reportPhotoLightbox" hidden role="dialog" aria-modal="true" aria-label="Pratinjau bukti foto">
    <button type="button" class="report-show-lightbox-close" id="reportPhotoLightboxClose" aria-label="Tutup pratinjau">&times;</button>
    <img src="" alt="" id="reportPhotoLightboxImage" referrerpolicy="no-referrer">
</div>
@endsection

@push('scripts')
<script>
    (() => {
        const chatFrame = document.getElementById('reportChatFrame');
        if (chatFrame) {
            chatFrame.scrollTop = chatFrame.scrollHeight;
        }

        document.querySelectorAll('.report-show-edit-trigger').forEach((button) => {
            button.addEventListener('click', () => {
                const article = button.closest('.report-show-message');
                if (!article) return;

                const bubble = article.querySelector('.report-show-message-bubble');
                const editForm = article.querySelector('.report-show-edit-form');
                if (!bubble || !editForm) return;

                bubble.hidden = true;
                editForm.hidden = false;
                button.setAttribute('aria-expanded', 'true');
                const field = editForm.querySelector('textarea');
                if (field) field.focus();
            });
        });

        document.querySelectorAll('.report-show-edit-cancel').forEach((button) => {
            button.addEventListener('click', () => {
                const article = button.closest('.report-show-message');
                if (!article) return;

                const bubble = article.querySelector('.report-show-message-bubble');
                const editForm = article.querySelector('.report-show-edit-form');
                const editButton = article.querySelector('.report-show-edit-trigger');
                if (bubble) bubble.hidden = false;
                if (editForm) {
                    const field = editForm.querySelector('textarea');
                    if (field) field.value = field.defaultValue;
                    editForm.hidden = true;
                }
                if (editButton) editButton.setAttribute('aria-expanded', 'false');
            });
        });

        const lightbox = document.getElementById('reportPhotoLightbox');
        const lightboxImage = document.getElementById('reportPhotoLightboxImage');
        const lightboxClose = document.getElementById('reportPhotoLightboxClose');

        const closeLightbox = () => {
            if (!lightbox || !lightboxImage) return;
            lightbox.hidden = true;
            lightboxImage.src = '';
            document.body.classList.remove('report-show-lightbox-open');
        };

        document.querySelectorAll('.report-show-photo').forEach((button) => {
            button.addEventListener('click', () => {
                if (!lightbox || !lightboxImage) return;
                lightboxImage.src = button.dataset.photoSrc || '';
                lightboxImage.alt = button.dataset.photoAlt || 'Bukti foto laporan';
                lightbox.hidden = false;
                document.body.classList.add('report-show-lightbox-open');
                if (lightboxClose) lightboxClose.focus();
            });
        });

        if (lightboxClose) lightboxClose.addEventListener('click', closeLightbox);
        if (lightbox) {
            lightbox.addEventListener('click', (event) => {
                if (event.target === lightbox) closeLightbox();
            });
        }
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') closeLightbox();
        });
    })();
</script>
@endpush
