@extends('layouts.app')

@section('title', 'Pengaturan')
@section('page-title', 'Pengaturan')
@section('page-description', 'Kelola foto profil dan keamanan akunmu.')

@push('styles')
    @vite('resources/css/pages/settings.css')
@endpush

@section('content')
@php
    $profileName = $user->name ?? 'Pengguna';
    $profileInitials = collect(preg_split('/\s+/', trim($profileName)))
        ->filter()
        ->take(2)
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('');

    $themeOptions = [
        'light' => [
            'label' => 'Terang',
            'description' => 'Tampilan standar dengan latar terang.',
        ],
        'dim' => [
            'label' => 'Redup',
            'description' => 'Latar gelap yang lebih lembut untuk mata.',
        ],
        'dark' => [
            'label' => 'Gelap',
            'description' => 'Latar gelap dengan kontras tinggi.',
        ],
    ];
@endphp

<div class="settings-page">
    <div class="settings-grid">
        <section class="settings-card" aria-labelledby="profile-heading">
            <div class="settings-card-heading">
                <span class="settings-card-heading__icon settings-card-heading__icon--profile" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none">
                        <circle cx="12" cy="8" r="3.5" />
                        <path d="M5 20c.7-3.2 3-5 7-5s6.3 1.8 7 5" />
                    </svg>
                </span>
                <div>
                    <h2 id="profile-heading">Profil akun</h2>
                    <p>Data akun dan foto yang tampil di navigasi.</p>
                </div>
            </div>

            <div class="settings-profile">
                <aside class="settings-profile__photo">
                    <div
                        class="settings-avatar"
                        id="settings-avatar-preview"
                        aria-label="Pratinjau foto profil"
                        data-existing-photo="{{ $profilePhotoUrl ? 'true' : 'false' }}"
                    >
                        @if ($profilePhotoUrl)
                            <img src="{{ $profilePhotoUrl }}" alt="Foto profil {{ $profileName }}">
                        @else
                            <span id="settings-avatar-initials">{{ $profileInitials ?: 'U' }}</span>
                        @endif
                    </div>

                    @if (session('status') === 'profile-photo-updated')
                        <div class="settings-message settings-message--success" role="status">
                            Foto berhasil diperbarui.
                        </div>
                    @elseif (session('status') === 'profile-photo-reset')
                        <div class="settings-message settings-message--success" role="status">
                            Foto berhasil dihapus.
                        </div>
                    @endif

                    <p class="settings-photo-note" id="profile-photo-note" hidden></p>

                    <form class="settings-form" id="profile-photo-upload-form" action="{{ route('settings.profile-photo.update') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PATCH')

                        <div class="settings-photo-buttons" data-photo-state="idle">
                            <label class="settings-photo-input" for="profile_photo" id="profile-photo-choose">Ubah foto</label>
                            <button
                                class="settings-photo-input settings-photo-input--danger"
                                id="profile-photo-delete-button"
                                type="button"
                                @unless ($profilePhotoUrl) hidden @endunless
                            >Hapus foto</button>
                        </div>

                        <div class="settings-photo-buttons" data-photo-state="uploading" hidden>
                            <button class="settings-submit-button" id="profile-photo-save-button" type="submit">Simpan foto</button>
                            <button class="settings-cancel-button" id="profile-photo-cancel-upload" type="button">Batalkan</button>
                        </div>

                        <div class="settings-photo-buttons" data-photo-state="confirming-delete" hidden>
                            <button class="settings-delete-button" id="profile-photo-delete-confirm" type="button">Ya, hapus</button>
                            <button class="settings-cancel-button" id="profile-photo-cancel-delete" type="button">Batal</button>
                        </div>

                        <input
                            class="settings-photo-file"
                            type="file"
                            id="profile_photo"
                            name="profile_photo"
                            accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                            required
                        >
                        @error('profile_photo')
                            <p class="settings-field-error" role="alert">{{ $message }}</p>
                        @enderror
                    </form>

                    {{-- Deletion is submitted by the dedicated form below so the
                         photo-upload form can keep the file input untouched. --}}
                    <form
                        id="profile-photo-reset-form"
                        action="{{ route('settings.profile-photo.reset') }}"
                        method="POST"
                        hidden
                    >
                        @csrf
                        @method('DELETE')
                    </form>
                </aside>

                <div class="settings-profile__info">
                    <div class="settings-profile__identity">
                        <h3>{{ $profileName }}</h3>
                        @if (!empty($user->role))
                            <span class="settings-role">{{ ucfirst($user->role) }}</span>
                        @endif
                    </div>

                    <dl class="settings-facts">
                        <div class="settings-fact">
                            <dt>NIS</dt>
                            <dd>{{ $user->nis ?: 'Belum tersedia' }}</dd>
                        </div>
                        <div class="settings-fact">
                            <dt>Kelas</dt>
                            <dd>{{ $user->classroom ?: 'Belum tersedia' }}</dd>
                        </div>
                        <div class="settings-fact">
                            <dt>Email</dt>
                            <dd>
                                <span id="settings-email-value">{{ $user->email ?: 'Belum tersedia' }}</span>
                                <button
                                    class="settings-fact-edit"
                                    type="button"
                                    id="settings-email-edit-toggle"
                                    aria-expanded="{{ $errors->has('email') ? 'true' : 'false' }}"
                                    aria-controls="settings-email-form"
                                >Ubah</button>
                            </dd>
                        </div>
                    </dl>

                    @if (session('status') === 'email-updated')
                        <div class="settings-message settings-message--success" role="status">
                            Email berhasil diperbarui.
                        </div>
                    @endif

                    <form
                        class="settings-form settings-email-form"
                        id="settings-email-form"
                        action="{{ route('settings.email.update') }}"
                        method="POST"
                        @unless ($errors->has('email')) hidden @endunless
                        data-open="{{ $errors->has('email') ? 'true' : 'false' }}"
                    >
                        @csrf
                        @method('PATCH')

                        <div class="settings-form-field">
                            <label for="email">Email baru</label>
                            <input
                                class="settings-text-input @error('email') is-invalid @enderror"
                                type="email"
                                id="email"
                                name="email"
                                value="{{ old('email', $user->email) }}"
                                maxlength="255"
                                required
                            >
                            @error('email')
                                <p class="settings-field-error" role="alert">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="settings-form-field">
                            <label for="email_confirmation">Ulangi email baru</label>
                            <input
                                class="settings-text-input"
                                type="email"
                                id="email_confirmation"
                                name="email_confirmation"
                                value="{{ old('email_confirmation') }}"
                                maxlength="255"
                                required
                            >
                        </div>

                        <div class="settings-form-actions">
                            <button class="settings-submit-button" type="submit">Simpan email</button>
                            <button
                                class="settings-cancel-button"
                                type="button"
                                id="settings-email-cancel"
                            >Batalkan</button>
                        </div>
                    </form>

                    {{-- Without JS the reveal button cannot work, so show the form outright. --}}
                    <noscript>
                        <style>#settings-email-form[hidden] { display: flex; }</style>
                    </noscript>
                </div>
            </div>
        </section>

        <section class="settings-card" aria-labelledby="appearance-heading">
            <div class="settings-card-heading">
                <span class="settings-card-heading__icon settings-card-heading__icon--appearance" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none">
                        <circle cx="12" cy="12" r="8" />
                        <path d="M12 4v16" />
                        <path d="M12 4a8 8 0 0 0 0 16" fill="currentColor" stroke="none" />
                    </svg>
                </span>
                <div>
                    <h2 id="appearance-heading">Mode tampilan</h2>
                    <p>Pilih tema yang paling nyaman untuk matamu. Pengaturan ini tersimpan di perangkat ini.</p>
                </div>
            </div>

            <fieldset class="settings-theme-fieldset">
                <legend class="settings-sr-only">Pilih mode tampilan</legend>

                <div class="settings-theme-options" role="radiogroup" aria-label="Mode tampilan">
                    @foreach ($themeOptions as $value => $option)
                        <label class="settings-theme-option" data-theme-option="{{ $value }}">
                            <input
                                type="radio"
                                name="theme"
                                value="{{ $value }}"
                                class="settings-theme-input"
                                data-theme-choice
                            >
                            <span class="settings-theme-preview settings-theme-preview--{{ $value }}" aria-hidden="true">
                                <span class="settings-theme-preview__bar"></span>
                                <span class="settings-theme-preview__body">
                                    <span class="settings-theme-preview__line"></span>
                                    <span class="settings-theme-preview__line settings-theme-preview__line--short"></span>
                                </span>
                            </span>
                            <span class="settings-theme-copy">
                                <span class="settings-theme-label">{{ $option['label'] }}</span>
                                <span class="settings-theme-description">{{ $option['description'] }}</span>
                            </span>
                            <span class="settings-theme-check" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none">
                                    <path d="m5 12 4 4L19 6" />
                                </svg>
                            </span>
                        </label>
                    @endforeach
                </div>
            </fieldset>

            <p class="settings-theme-note" role="status" data-theme-note hidden>
                Mode tampilan disimpan di perangkat ini.
            </p>
        </section>

        <section class="settings-card" aria-labelledby="password-heading">
            <div class="settings-card-heading">
                <span class="settings-card-heading__icon settings-card-heading__icon--security" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none">
                        <rect x="5" y="10" width="14" height="11" rx="2" />
                        <path d="M8 10V7a4 4 0 0 1 8 0v3M12 14v3" />
                    </svg>
                </span>
                <div>
                    <h2 id="password-heading">Ubah kata sandi</h2>
                    <p>Gunakan kata sandi baru yang sulit ditebak.</p>
                </div>
            </div>

            @if (session('status') === 'password-updated')
                <div class="settings-message settings-message--success" role="status">
                    Kata sandi berhasil diperbarui.
                </div>
            @endif

            <form class="settings-form" action="{{ route('settings.password.update') }}" method="POST">
                @csrf
                @method('PATCH')

                <div class="settings-form-field">
                    <label for="current_password">Kata sandi saat ini</label>
                    <input
                        class="settings-text-input @error('current_password') is-invalid @enderror"
                        type="password"
                        id="current_password"
                        name="current_password"
                        autocomplete="current-password"
                        required
                    >
                    @error('current_password')
                        <p class="settings-field-error" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <div class="settings-form-field">
                    <label for="password">Kata sandi baru</label>
                    <input
                        class="settings-text-input @error('password') is-invalid @enderror"
                        type="password"
                        id="password"
                        name="password"
                        autocomplete="new-password"
                        minlength="8"
                        required
                    >
                    <p class="settings-field-help">Minimal 8 karakter.</p>
                    @error('password')
                        <p class="settings-field-error" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <div class="settings-form-field">
                    <label for="password_confirmation">Konfirmasi kata sandi baru</label>
                    <input
                        class="settings-text-input @error('password_confirmation') is-invalid @enderror"
                        type="password"
                        id="password_confirmation"
                        name="password_confirmation"
                        autocomplete="new-password"
                        minlength="8"
                        required
                    >
                    @error('password_confirmation')
                        <p class="settings-field-error" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <div class="settings-form-actions">
                    <button class="settings-submit-button" type="submit">Perbarui kata sandi</button>
                </div>
            </form>
        </section>
    </div>
</div>
@endsection

@push('scripts')
<script>
    (() => {
        const input = document.getElementById('profile_photo');
        const preview = document.getElementById('settings-avatar-preview');
        const note = document.getElementById('profile-photo-note');
        const buttons = document.querySelectorAll('#profile-photo-upload-form .settings-photo-buttons');
        const deleteForm = document.getElementById('profile-photo-reset-form');

        if (!input || !preview) return;

        // Keep the server-rendered avatar so Cancel can discard an unsaved preview.
        const originalPreviewMarkup = preview.innerHTML;
        let previewUrl = null;

        // Exactly one button group is visible per state, so the panel never
        // shows more than two controls at once.
        const showState = (state, message) => {
            buttons.forEach((group) => {
                group.hidden = group.dataset.photoState !== state;
            });

            if (note) {
                note.textContent = message || '';
                note.hidden = !message;
            }
        };

        const discardPreview = () => {
            if (previewUrl) {
                URL.revokeObjectURL(previewUrl);
                previewUrl = null;
            }

            input.value = '';
            preview.innerHTML = originalPreviewMarkup;
            showState('idle');
        };

        document.getElementById('profile-photo-cancel-upload')?.addEventListener('click', discardPreview);
        document.getElementById('profile-photo-cancel-delete')?.addEventListener('click', () => showState('idle'));

        document.getElementById('profile-photo-delete-button')?.addEventListener('click', () => {
            showState('confirming-delete', 'Foto akan dihapus dan avatar kembali menampilkan inisial nama.');
        });

        document.getElementById('profile-photo-delete-confirm')?.addEventListener('click', () => {
            deleteForm?.submit();
        });

        input.addEventListener('change', () => {
            const file = input.files && input.files[0];
            if (!file) return;

            if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
                input.value = '';
                return;
            }

            if (previewUrl) URL.revokeObjectURL(previewUrl);
            previewUrl = URL.createObjectURL(file);

            let image = preview.querySelector('img');
            if (!image) {
                preview.replaceChildren();
                image = document.createElement('img');
                image.alt = 'Pratinjau foto profil baru';
                preview.appendChild(image);
            }
            image.src = previewUrl;

            showState('uploading', 'Pratinjau belum disimpan. Klik Simpan foto untuk menerapkan.');
        });

        // Returning to the page after a failed upload should show the idle state.
        showState('idle');
    })();

    // Email change: reveal the inline form on demand. The form is open by
    // default when validation failed, so the errors are visible.
    (() => {
        const toggle = document.getElementById('settings-email-edit-toggle');
        const form = document.getElementById('settings-email-form');
        const cancel = document.getElementById('settings-email-cancel');

        if (!toggle || !form) return;

        const setOpen = (open) => {
            form.hidden = !open;
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');

            if (open) {
                form.querySelector('input[name="email"]')?.focus();
            }
        };

        toggle.addEventListener('click', () => setOpen(form.hidden));
        cancel?.addEventListener('click', () => setOpen(false));

        // A server-side validation failure re-renders with the form open.
        setOpen(form.dataset.open === 'true');
    })();
</script>
@endpush
