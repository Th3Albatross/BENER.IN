@extends('layouts.app')

@push('styles')
    @vite('resources/css/pages/admin.css')
@endpush

@section('title', 'Tambah Pengguna')
@section('page-title', 'Tambah Pengguna')
@section('page-description', 'Buat akun siswa atau petugas baru.')

@section('content')
<div class="admin-page">
    <section class="admin-card" aria-labelledby="create-user-heading">
        <div class="admin-card-heading">
            <div>
                <h2 id="create-user-heading" class="admin-card-title">Akun Baru</h2>
                <p class="admin-card-subtitle">Isi data pengguna lalu simpan.</p>
            </div>
        </div>

        @include('admin.users._form', [
            'isEdit' => false,
            'userRecord' => null,
            'roles' => $roles,
        ])
    </section>
</div>
@endsection
