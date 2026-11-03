@extends('layouts.app')

@push('styles')
    @vite('resources/css/pages/admin.css')
@endpush

@section('title', 'Kelola Pengguna')
@section('page-title', 'Pengguna')
@section('page-description', 'Kelola akun siswa dan petugas.')

@section('content')
<div class="admin-page">
    @include('admin.partials.flash')

    @if ($errors->has('user'))
        <div class="admin-message admin-message--error" role="alert">{{ $errors->first('user') }}</div>
    @endif

    <section class="admin-card" aria-labelledby="users-heading">
        <div class="admin-card-heading">
            <div>
                <h2 id="users-heading" class="admin-card-title">Daftar Pengguna</h2>
                <p class="admin-card-subtitle">
                    {{ number_format($users->total(), 0, ',', '.') }} akun terdaftar
                    @if ($roleFilter)
                        dengan peran <strong>{{ ucfirst($roleFilter) }}</strong>.
                        <a href="{{ route('admin.users.index') }}">Tampilkan semua</a>
                    @else
                        .
                    @endif
                </p>
            </div>

            <a href="{{ route('admin.users.create') }}" class="admin-button admin-button--primary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path d="M12 5v14M5 12h14" />
                </svg>
                <span>Tambah pengguna</span>
            </a>
        </div>

        @if ($users->isEmpty())
            <div class="admin-empty">
                <strong>Belum ada pengguna</strong>
                Tambahkan akun siswa atau petugas untuk memulai.
            </div>
        @else
            <div class="admin-table-wrapper">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th scope="col">Nama</th>
                            <th scope="col">Peran</th>
                            <th scope="col">Laporan</th>
                            <th scope="col">Pesan</th>
                            <th scope="col">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($users as $user)
                            <tr>
                                <td data-label="Nama">
                                    <span class="admin-table-primary">{{ $user->name }}</span>
                                    <span class="admin-table-secondary">{{ $user->nis ? 'NIS '.$user->nis : 'NIS belum tersedia' }}</span>
                                    <span class="admin-table-secondary">{{ $user->classroom ? 'Kelas '.$user->classroom : 'Kelas belum tersedia' }}</span>
                                    <span class="admin-table-secondary">{{ $user->email ?: 'Email belum tersedia' }}</span>
                                </td>
                                <td data-label="Peran">
                                    @php
                                        $roleClass = match ($user->role) {
                                            'admin' => 'admin-role--admin',
                                            'petugas' => 'admin-role--petugas',
                                            default => 'admin-role--siswa',
                                        };
                                    @endphp
                                    <span class="admin-role {{ $roleClass }}">{{ ucfirst($user->role) }}</span>
                                </td>
                                <td data-label="Laporan"><span class="admin-count-pill">{{ number_format($user->reports_count, 0, ',', '.') }}</span></td>
                                <td data-label="Pesan"><span class="admin-count-pill">{{ number_format($user->responses_count, 0, ',', '.') }}</span></td>
                                <td data-label="Aksi">
                                    @if ($user->isAdmin())
                                        <span class="admin-table-secondary">Tidak dapat diubah</span>
                                    @else
                                        <div class="admin-table-actions">
                                            <a href="{{ route('admin.users.edit', $user) }}" class="admin-button admin-button--secondary admin-button--small">Ubah</a>

                                            <form
                                                action="{{ route('admin.users.destroy', $user) }}"
                                                method="POST"
                                                onsubmit="return confirm('Hapus pengguna {{ $user->name }}?')"
                                            >
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="admin-button admin-button--danger admin-button--small">Hapus</button>
                                            </form>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @include('admin.partials.pagination', ['paginator' => $users])
        @endif
    </section>
</div>
@endsection
