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

    <div class="admin-field" data-role-field="siswa">
        <label for="nis">NIS</label>
        <input
            class="admin-input @error('nis') is-invalid @enderror"
            type="text"
            id="nis"
            name="nis"
            value="{{ old('nis', data_get($userRecord, 'nis')) }}"
            inputmode="numeric"
            maxlength="20"
        >
        <p class="admin-field-help">Nomor induk siswa, hanya berisi angka.</p>
        @error('nis')
            <p class="admin-field-error" role="alert">{{ $message }}</p>
        @enderror
    </div>

    <div class="admin-field" data-role-field="siswa">
        <label for="classroom">Kelas</label>
        <input
            class="admin-input @error('classroom') is-invalid @enderror"
            type="text"
            id="classroom"
            name="classroom"
            value="{{ old('classroom', data_get($userRecord, 'classroom')) }}"
            maxlength="20"
            placeholder="Contoh: XI-1"
        >
        @error('classroom')
            <p class="admin-field-error" role="alert">{{ $message }}</p>
        @enderror
    </div>

    <div class="admin-field" data-role-field="staff">
        <label for="email">Email</label>
        <input
            class="admin-input @error('email') is-invalid @enderror"
            type="email"
            id="email"
            name="email"
            value="{{ old('email', data_get($userRecord, 'email')) }}"
            maxlength="255"
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

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const form = document.querySelector('.admin-form');
        if (!form) return;

        const roleSelect = form.querySelector('#role');
        const fields = form.querySelectorAll('[data-role-field]');

        const sync = () => {
            const isStudent = roleSelect?.value === 'siswa';

            fields.forEach((field) => {
                const matches = field.dataset.roleField === (isStudent ? 'siswa' : 'staff');
                field.hidden = !matches;

                // Only a visible field can be required; the server re-checks anyway.
                field.querySelectorAll('input').forEach((input) => {
                    input.required = matches && (input.id !== 'email' || !isStudent);
                });
            });
        };

        roleSelect?.addEventListener('change', sync);
        sync();
    });
</script>
@endpush
