{{-- Success feedback for master-data changes, keyed off the session status. --}}
@php
    $statusMessages = [
        'user-created' => 'Pengguna baru berhasil ditambahkan.',
        'user-updated' => 'Data pengguna berhasil diperbarui.',
        'user-deleted' => 'Pengguna berhasil dihapus.',
        'facility-created' => 'Fasilitas baru berhasil ditambahkan.',
        'facility-updated' => 'Data fasilitas berhasil diperbarui.',
        'facility-deleted' => 'Fasilitas berhasil dihapus.',
        'category-created' => 'Kategori baru berhasil ditambahkan.',
        'category-updated' => 'Data kategori berhasil diperbarui.',
        'category-deleted' => 'Kategori berhasil dihapus.',
    ];
@endphp

@if (session('status') && isset($statusMessages[session('status')]))
    <div class="admin-message admin-message--success" role="status">
        {{ $statusMessages[session('status')] }}
    </div>
@endif
