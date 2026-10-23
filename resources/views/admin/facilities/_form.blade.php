{{-- Shared create/edit form for facilities. --}}
@php
    $isEdit = $isEdit ?? false;
    $facilityRecord = $facilityRecord ?? null;
@endphp

<form
    class="admin-form"
    method="POST"
    action="{{ $isEdit ? route('admin.facilities.update', $facilityRecord) : route('admin.facilities.store') }}"
>
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    <div class="admin-field">
        <label for="name">Nama fasilitas</label>
        <input
            class="admin-input @error('name') is-invalid @enderror"
            type="text"
            id="name"
            name="name"
            value="{{ old('name', data_get($facilityRecord, 'name')) }}"
            maxlength="255"
            required
        >
        @error('name')
            <p class="admin-field-error" role="alert">{{ $message }}</p>
        @enderror
    </div>

    <div class="admin-form-actions">
        <button type="submit" class="admin-button admin-button--primary">
            {{ $isEdit ? 'Simpan perubahan' : 'Tambah fasilitas' }}
        </button>
        <a href="{{ route('admin.facilities.index') }}" class="admin-button admin-button--secondary">Batal</a>
    </div>
</form>
