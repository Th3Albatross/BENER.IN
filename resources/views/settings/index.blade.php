@extends('layouts.app')

@section('title', 'Pengaturan')
@section('page-title', 'Pengaturan')
@section('page-description', 'Kelola foto profil dan keamanan akunmu.')

@push('styles')
    @vite('resources/css/settings.css')
@endpush

@section('content')
@php
    $profileName = $user->name ?? 'Pengguna';
    $profileEmail = $user->email ?? 'Email belum tersedia';
    $profileInitials = collect(preg_split('/\s+/', trim($profileName)))
        ->filter()
        ->take(2)
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('');
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
                <div class="settings-avatar" id="settings-avatar-preview" aria-label="Pratinjau foto profil">
                    @if ($profilePhotoUrl)
                        <img src="{{ $profilePhotoUrl }}" alt="Foto profil {{ $profileName }}">
                    @else
                        <span id="settings-avatar-initials">{{ $profileInitials ?: 'U' }}</span>
                    @endif
                </div>
                <div class="settings-profile-summary__text">
                    <h3>{{ $profileName }}</h3>
                    <p>{{ $profileEmail }}</p>
                    @if (!empty($user->role))
                        <span class="settings-role">{{ ucfirst($user->role) }}</span>
                    @endif
                </div>
            </div>

            @if (session('status') === 'profile-photo-updated')
                <div class="settings-message settings-message--success" role="status">
                    Foto profil berhasil diperbarui.
                </div>
            @endif

            <form class="settings-form" action="{{ route('settings.profile-photo.update') }}" method="POST" enctype="multipart/form-data">
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

                <div class="settings-form-actions">
                    <button class="settings-submit-button" type="submit">Simpan foto</button>
                </div>
            </form>
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

        if (!input || !preview) return;

        let previewUrl = null;

        input.addEventListener('change', () => {
            const file = input.files && input.files[0];
            if (!file || !file.type.startsWith('image/')) return;

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
        });
    })();
</script>
@endpush
