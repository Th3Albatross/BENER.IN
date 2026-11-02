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
    $profileNis = $user->nis ?: 'NIS belum tersedia';
    $profileClassroom = $user->classroom ?: 'Kelas belum tersedia';
    $profileEmail = $user->email ?: 'Email belum tersedia';
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
                    <h2 id="profile-heading">Foto profil</h2>
                    <p>Foto ini akan ditampilkan di navigasi akunmu.</p>
                </div>
            </div>

            <div class="settings-profile-summary">
                <div
                    class="settings-avatar {{ $profilePhotoUrl ? 'settings-avatar--has-photo' : '' }}"
                    id="settings-avatar-preview"
                    aria-label="Pratinjau foto profil"
                    data-existing-photo="{{ $profilePhotoUrl ? 'true' : 'false' }}"
                >
                    @if ($profilePhotoUrl)
                        <img src="{{ $profilePhotoUrl }}" alt="Foto profil {{ $profileName }}">
                        <button
                            class="settings-avatar-remove-toggle"
                            type="button"
                            id="settings-avatar-remove-toggle"
                            aria-label="Tandai foto profil untuk dihapus"
                            aria-pressed="false"
                            title="Klik untuk menandai foto profil agar dihapus"
                        >
                            <span class="settings-avatar-remove-overlay" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none">
                                    <path d="m7 7 10 10M17 7 7 17" />
                                </svg>
                            </span>
                        </button>
                    @else
                        <span id="settings-avatar-initials">{{ $profileInitials ?: 'U' }}</span>
                    @endif
                </div>
                <div class="settings-profile-summary__text">
                    <h3>{{ $profileName }}</h3>
                    <p>{{ $profileNis }}</p>
                    <p>{{ $profileClassroom }}</p>
                    <p>{{ $profileEmail }}</p>
                    @if (!empty($user->role))
                        <span class="settings-role">{{ ucfirst($user->role) }}</span>
                    @endif
                </div>
            </div>

            @if ($profilePhotoUrl)
                <p class="settings-avatar-hint" id="profile-photo-reset-hint">Klik foto profil untuk menandai bahwa foto ingin dihapus.</p>
            @endif

            @if (session('status') === 'profile-photo-updated')
                <div class="settings-message settings-message--success" role="status">
                    Foto profil berhasil diperbarui.
                </div>
            @elseif (session('status') === 'profile-photo-reset')
                <div class="settings-message settings-message--success" role="status">
                    Foto profil berhasil dihapus. Inisial nama digunakan kembali.
                </div>
            @endif

            {{-- The reset form stays in the DOM so the shared action button can submit it. --}}
            <form
                class="settings-form-carrier"
                id="profile-photo-reset-form"
                action="{{ route('settings.profile-photo.reset') }}"
                method="POST"
                aria-hidden="true"
                tabindex="-1"
            >
                @csrf
                @method('DELETE')
            </form>

            <form class="settings-form" id="profile-photo-upload-form" action="{{ route('settings.profile-photo.update') }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PATCH')

                <div class="settings-form-field">
                    <label for="profile_photo">Pilih foto baru</label>
                    <input
                        class="settings-file-input"
                        type="file"
                        id="profile_photo"
                        name="profile_photo"
                        accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                        required
                    >
                    <p class="settings-field-help">Format JPG, PNG, atau WebP. Ukuran maksimal 2 MB.</p>
                    @error('profile_photo')
                        <p class="settings-field-error" role="alert">{{ $message }}</p>
                    @enderror
                </div>
            </form>

            <p class="settings-reset-confirmation" id="profile-photo-reset-confirmation" hidden>
                Hapus foto profil ini? Avatar akan kembali menampilkan inisial nama.
            </p>

            <div class="settings-form-actions settings-profile-actions">
                <button
                    class="settings-submit-button"
                    id="profile-photo-save-button"
                    type="submit"
                    form="profile-photo-upload-form"
                >Simpan foto</button>
                <button
                    class="settings-delete-button"
                    id="profile-photo-delete-button"
                    type="submit"
                    form="profile-photo-reset-form"
                    hidden
                >Hapus foto profil</button>
                <button
                    class="settings-cancel-button"
                    type="button"
                    id="cancel-profile-photo-reset"
                    hidden
                >Batalkan</button>
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
        let removeToggle = document.getElementById('settings-avatar-remove-toggle');
        const resetHint = document.getElementById('profile-photo-reset-hint');
        const resetConfirmation = document.getElementById('profile-photo-reset-confirmation');
        const uploadForm = document.getElementById('profile-photo-upload-form');
        const saveButton = document.getElementById('profile-photo-save-button');
        const deleteButton = document.getElementById('profile-photo-delete-button');
        const cancelReset = document.getElementById('cancel-profile-photo-reset');

        if (!input || !preview) return;

        // Keep the server-rendered avatar so Cancel can discard an unsaved preview.
        const originalPreviewMarkup = preview.innerHTML;
        const defaultHint = 'Klik foto profil untuk menandai bahwa foto ingin dihapus.';
        const uploadHint = 'Pratinjau foto baru belum disimpan. Klik Batalkan untuk membuang perubahan.';
        let previewUrl = null;
        let removalSelected = false;
        let uploadPending = false;

        const updateHint = () => {
            if (!resetHint) return;

            if (removalSelected) {
                resetHint.textContent = 'Foto profil ditandai untuk dihapus. Konfirmasikan dengan tombol Hapus foto profil.';
            } else if (uploadPending) {
                resetHint.textContent = uploadHint;
            } else {
                resetHint.textContent = defaultHint;
            }

            resetHint.classList.toggle('settings-avatar-hint--selected', removalSelected);
        };

        const setRemovalSelected = (selected) => {
            // A pending upload and a deletion request are mutually exclusive modes.
            if (uploadPending && selected) return;

            removalSelected = selected;
            preview.classList.toggle('is-removal-selected', selected);

            if (removeToggle) {
                removeToggle.setAttribute('aria-pressed', selected ? 'true' : 'false');
                removeToggle.setAttribute(
                    'aria-label',
                    selected ? 'Batalkan pilihan penghapusan foto profil' : 'Tandai foto profil untuk dihapus'
                );
            }

            if (uploadForm) {
                uploadForm.hidden = selected;
                uploadForm.setAttribute('aria-hidden', selected ? 'true' : 'false');
            }
            if (saveButton) saveButton.hidden = selected;
            if (deleteButton) deleteButton.hidden = !selected;
            if (cancelReset) cancelReset.hidden = !(selected || uploadPending);
            if (resetConfirmation) resetConfirmation.hidden = !selected;

            updateHint();
        };

        const handleRemoveToggle = () => {
            if (uploadPending || (input.files && input.files.length > 0)) return;
            setRemovalSelected(!removalSelected);
        };

        const bindRemoveToggle = () => {
            removeToggle = preview.querySelector('#settings-avatar-remove-toggle');
            if (!removeToggle) return;

            removeToggle.hidden = false;
            removeToggle.addEventListener('click', handleRemoveToggle);
        };

        const restoreOriginalAvatar = () => {
            if (previewUrl) {
                URL.revokeObjectURL(previewUrl);
                previewUrl = null;
            }

            input.value = '';
            uploadPending = false;
            removalSelected = false;
            preview.innerHTML = originalPreviewMarkup;
            preview.classList.remove('is-removal-selected');
            bindRemoveToggle();
            setRemovalSelected(false);
        };

        bindRemoveToggle();

        if (cancelReset) {
            cancelReset.addEventListener('click', () => {
                if (uploadPending) {
                    // Discard an unsaved upload and restore the currently saved photo/initials.
                    restoreOriginalAvatar();
                    return;
                }

                // Exit deletion mode without changing the saved profile photo.
                setRemovalSelected(false);
            });
        }

        input.addEventListener('change', () => {
            const file = input.files && input.files[0];
            if (!file) return;

            if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
                input.value = '';
                return;
            }

            setRemovalSelected(false);
            uploadPending = true;

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

            // While an upload is pending, show Save and Cancel together; hide the
            // removal overlay because the preview is not yet the saved profile photo.
            if (removeToggle) removeToggle.hidden = true;
            if (saveButton) saveButton.hidden = false;
            if (deleteButton) deleteButton.hidden = true;
            if (cancelReset) cancelReset.hidden = false;
            if (resetConfirmation) resetConfirmation.hidden = true;
            if (uploadForm) {
                uploadForm.hidden = false;
                uploadForm.setAttribute('aria-hidden', 'false');
            }
            updateHint();
        });
    })();
</script>
@endpush
