{{-- Shared create/edit form for user accounts. --}}
@php
    $isEdit = $isEdit ?? false;
    $userRecord = $userRecord ?? null;
@endphp

<form
    class="admin-form"
    method="POST"
    action="{{ $isEdit ? route('admin.users.update', $userRecord) : route('admin.users.store') }}"
>
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    <div class="admin-field">
        <label for="name">Nama lengkap</label>
        <input
            class="admin-input @error('name') is-invalid @enderror"
            type="text"
            id="name"
            name="name"
            value="{{ old('name', data_get($userRecord, 'name')) }}"
            maxlength="255"
            required
        >
        @error('name')
            <p class="admin-field-error" role="alert">{{ $message }}</p>
        @enderror
    </div>

    <div class="admin-field">
        <label for="email">Email</label>
        <input
            class="admin-input @error('email') is-invalid @enderror"
            type="email"
            id="email"
            name="email"
            value="{{ old('email', data_get($userRecord, 'email')) }}"
            maxlength="255"
            required
        >
        @error('email')
            <p class="admin-field-error" role="alert">{{ $message }}</p>
        @enderror
    </div>

    <div class="admin-field">
        <label for="role">Peran</label>
        <select class="admin-select @error('role') is-invalid @enderror" id="role" name="role" required>
            @foreach ($roles as $role)
                <option value="{{ $role }}" @selected(old('role', data_get($userRecord, 'role')) === $role)>
                    {{ ucfirst($role) }}
                </option>
            @endforeach
        </select>
        <p class="admin-field-help">Akun admin tidak dapat dibuat dari halaman ini.</p>
        @error('role')
            <p class="admin-field-error" role="alert">{{ $message }}</p>
        @enderror
    </div>

    <div class="admin-field">
        <label for="password">{{ $isEdit ? 'Kata sandi baru' : 'Kata sandi awal' }}</label>
        <input
            class="admin-input @error('password') is-invalid @enderror"
            type="password"
            id="password"
            name="password"
            autocomplete="new-password"
            minlength="8"
            @required(! $isEdit)
        >
        <p class="admin-field-help">
            {{ $isEdit
                ? 'Biarkan kosong jika tidak ingin mengubah kata sandi.'
                : 'Minimal 8 karakter. Beritahukan kata sandi ini kepada pengguna.' }}
        </p>
        @error('password')
            <p class="admin-field-error" role="alert">{{ $message }}</p>
        @enderror
    </div>

    <div class="admin-field">
        <label for="password_confirmation">Konfirmasi kata sandi</label>
        <input
            class="admin-input"
            type="password"
            id="password_confirmation"
            name="password_confirmation"
            autocomplete="new-password"
            minlength="8"
            @required(! $isEdit)
        >
    </div>

    <div class="admin-form-actions">
        <button type="submit" class="admin-button admin-button--primary">
            {{ $isEdit ? 'Simpan perubahan' : 'Tambah pengguna' }}
        </button>
        <a href="{{ route('admin.users.index') }}" class="admin-button admin-button--secondary">Batal</a>
    </div>
</form>
