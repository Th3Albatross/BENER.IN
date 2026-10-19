@extends('layouts.app')

@push('styles')
    @vite('resources/css/discussion.css')
@endpush

@section('title', 'Diskusi Laporan')
@section('page-title', 'Diskusi Laporan')
@section('page-description', 'Percakapan antara pelapor dan petugas.')

@section('content')
@php
    $reportData = $_report ?? $report ?? null;
    $responses = collect($_responses ?? $responses ?? []);

    if (!$reportData) {
        abort(404);
    }

    $returnToDiscussions = request()->query('from') === 'discussions';
    $backUrl = route('reports.showDetail', ['uuid' => $reportData->identifier]);
    $backLabel = 'Kembali ke Detail Laporan';

    if ($returnToDiscussions) {
        $backQuery = [
            'status' => request()->query('status', 'all'),
            'page' => request()->query('page'),
        ];
        $backQuery = array_filter($backQuery, fn ($value) => $value !== null && $value !== '');
        $backUrl = route('discussions.index', $backQuery);
        $backLabel = 'Kembali ke Daftar Diskusi';
    }
@endphp

<div class="discussion-page">
    <div class="discussion-toolbar">
        <a href="{{ $backUrl }}" class="discussion-back-link">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m15 18-6-6 6-6" /></svg>
            {{ $backLabel }}
        </a>
        <span class="discussion-report-reference">{{ $reportData->identifier }}</span>
    </div>

    @if (session('success'))
        <div class="discussion-alert discussion-alert--success" role="status">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="discussion-alert discussion-alert--error" role="alert">
            <strong>Pesan belum dapat diproses.</strong>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="discussion-report-heading">
        <div class="discussion-report-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20 11.5a7.5 7.5 0 0 1-7.5 7.5H7l-3 2v-6.5A7.5 7.5 0 1 1 20 11.5Z" /><path d="M8 10h8M8 13.5h5" /></svg>
        </div>
        <div class="discussion-report-heading-copy">
            <p class="discussion-eyebrow">DISKUSI LAPORAN</p>
            <h2>{{ $reportData->title }}</h2>
            <div class="discussion-report-meta">
                <span class="discussion-status discussion-status--{{ strtolower((string) ($reportData->status ?? 'pending')) }}">{{ $reportData->status ?? 'Pending' }}</span>
                @if (!empty($reportData->facilities_name))
                    <span>{{ $reportData->facilities_name }}</span>
                @endif
            </div>
        </div>
    </section>

    <section class="discussion-card" aria-labelledby="discussionHistoryTitle">
        <header class="discussion-card-header">
            <div>
                <h3 id="discussionHistoryTitle">Riwayat Percakapan</h3>
                <p>Pesan lama berada di atas dan pesan terbaru di bawah.</p>
            </div>
            <span class="discussion-count">{{ $responses->count() }} pesan</span>
        </header>

        <div class="discussion-card-body">
            <div class="discussion-chat-frame" id="discussionChatFrame" aria-label="Riwayat diskusi">
                @forelse ($responses as $response)
                    @php
                        $isOwnResponse = (int) $response->users_id === (int) auth()->id();
                        $senderName = $response->user_name ?? 'Pengguna';
                        $role = strtolower((string) ($response->user_role ?? ''));
                        $senderRole = in_array($role, ['siswa', 'student'], true)
                            ? 'Siswa'
                            : (in_array($role, ['petugas', 'attendant', 'staff'], true) ? 'Petugas' : ucfirst($role ?: 'Pengguna'));
                        $responseDate = $response->created_at ?? null;
                    @endphp

                    <article class="discussion-message {{ $isOwnResponse ? 'discussion-message--mine' : '' }}" data-response-id="{{ $response->id }}">
                        <div class="discussion-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr((string) $senderName, 0, 1)) }}</div>
                        <div class="discussion-message-content">
                            <div class="discussion-message-meta">
                                <strong>{{ $senderName }}</strong>
                                <span class="discussion-role">{{ $senderRole }}</span>
                                @if ($responseDate)
                                    <time datetime="{{ \Illuminate\Support\Carbon::parse($responseDate)->toIso8601String() }}">{{ \Illuminate\Support\Carbon::parse($responseDate)->format('d M Y, H:i') }}</time>
                                @endif
                            </div>

                            <div class="discussion-bubble">
                                <p class="discussion-message-text">{{ $response->message }}</p>
                                @if ($isOwnResponse)
                                    <button type="button" class="discussion-edit-trigger" aria-expanded="false">Edit</button>
                                @endif
                            </div>

                            @if ($isOwnResponse)
                                <form class="discussion-edit-form" action="{{ route('responses.update', ['uuid' => $reportData->identifier, 'responseId' => $response->id]) }}" method="POST" hidden>
                                    @csrf
                                    @method('PATCH')
                                    @if ($returnToDiscussions)
                                        <input type="hidden" name="from" value="discussions">
                                        <input type="hidden" name="status" value="{{ request()->query('status', 'all') }}">
                                        @if (request()->query('page'))
                                            <input type="hidden" name="page" value="{{ request()->query('page') }}">
                                        @endif
                                    @endif
                                    <label class="discussion-sr-only" for="edit-message-{{ $response->id }}">Edit pesan</label>
                                    <textarea id="edit-message-{{ $response->id }}" name="message" rows="3" maxlength="5000" required>{{ $response->message }}</textarea>
                                    <div class="discussion-form-actions">
                                        <button type="button" class="discussion-button discussion-button--secondary discussion-edit-cancel">Batal</button>
                                        <button type="submit" class="discussion-button discussion-button--primary">Simpan</button>
                                    </div>
                                </form>
                            @endif
                        </div>
                    </article>
                @empty
                    <div class="discussion-empty-state">
                        <span class="discussion-empty-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M20 11.5a7.5 7.5 0 0 1-7.5 7.5H7l-3 2v-6.5A7.5 7.5 0 1 1 20 11.5Z" /><path d="M8 10h8M8 13.5h5" /></svg>
                        </span>
                        <strong>Belum ada percakapan</strong>
                        <span>Pesan dari pelapor dan petugas akan muncul di sini.</span>
                    </div>
                @endforelse
            </div>

            <form class="discussion-composer" action="{{ route('responses.store', ['uuid' => $reportData->identifier]) }}" method="POST">
                @csrf
                @if ($returnToDiscussions)
                    <input type="hidden" name="from" value="discussions">
                    <input type="hidden" name="status" value="{{ request()->query('status', 'all') }}">
                    @if (request()->query('page'))
                        <input type="hidden" name="page" value="{{ request()->query('page') }}">
                    @endif
                @endif
                <label for="new-discussion-message">Tulis pesan</label>
                <textarea id="new-discussion-message" name="message" rows="3" maxlength="5000" placeholder="Tulis pesan untuk pelapor atau petugas..." required>{{ old('message') }}</textarea>
                <div class="discussion-composer-footer">
                    <p>Gunakan bahasa yang sopan dan sampaikan informasi yang relevan dengan laporan.</p>
                    <button type="submit" class="discussion-button discussion-button--primary">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m21 3-7.5 18-3.5-7-7-3.5z" /><path d="M10 14 21 3" /></svg>
                        Kirim Pesan
                    </button>
                </div>
            </form>
        </div>
    </section>
</div>
@endsection

@push('scripts')
<script>
    (() => {
        const chatFrame = document.getElementById('discussionChatFrame');
        if (chatFrame) chatFrame.scrollTop = chatFrame.scrollHeight;

        document.querySelectorAll('.discussion-edit-trigger').forEach((button) => {
            button.addEventListener('click', () => {
                const message = button.closest('.discussion-message');
                const bubble = message?.querySelector('.discussion-bubble');
                const form = message?.querySelector('.discussion-edit-form');
                if (!bubble || !form) return;
                bubble.hidden = true;
                form.hidden = false;
                button.setAttribute('aria-expanded', 'true');
                form.querySelector('textarea')?.focus();
            });
        });

        document.querySelectorAll('.discussion-edit-cancel').forEach((button) => {
            button.addEventListener('click', () => {
                const message = button.closest('.discussion-message');
                const bubble = message?.querySelector('.discussion-bubble');
                const form = message?.querySelector('.discussion-edit-form');
                const editButton = message?.querySelector('.discussion-edit-trigger');
                if (bubble) bubble.hidden = false;
                if (form) {
                    const field = form.querySelector('textarea');
                    if (field) field.value = field.defaultValue;
                    form.hidden = true;
                }
                if (editButton) editButton.setAttribute('aria-expanded', 'false');
            });
        });
    })();
</script>
@endpush
