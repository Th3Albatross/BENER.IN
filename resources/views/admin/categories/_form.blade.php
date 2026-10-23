{{-- Shared create/edit form for categories. --}}
@php
    $isEdit = $isEdit ?? false;
    $categoryRecord = $categoryRecord ?? null;
@endphp

<form
    class="admin-form"
    method="POST"
    action="{{ $isEdit ? route('admin.categories.update', $categoryRecord) : route('admin.categories.store') }}"
>
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    <div class="admin-field">
        <label for="name">Nama kategori</label>
        <input
            class="admin-input @error('name') is-invalid @enderror"
            type="text"
            id="name"
            name="name"
            value="{{ old('name', data_get($categoryRecord, 'name')) }}"
            maxlength="255"
            required
        >
        @error('name')
            <p class="admin-field-error" role="alert">{{ $message }}</p>
        @enderror
    </div>

    <div class="admin-form-actions">
        <button type="submit" class="admin-button admin-button--primary">
            {{ $isEdit ? 'Simpan perubahan' : 'Tambah kategori' }}
        </button>
        <a href="{{ route('admin.categories.index') }}" class="admin-button admin-button--secondary">Batal</a>
    </div>
</form>
