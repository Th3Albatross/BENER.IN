@extends('layouts.app')

@push('styles')
    @vite('resources/css/admin.css')
@endpush

@section('title', 'Ubah Pengguna')
@section('page-title', 'Ubah Pengguna')
@section('page-description', 'Perbarui data akun pengguna.')

@section('content')
<div class="admin-page">
    <section class="admin-card" aria-labelledby="edit-user-heading">
        <div class="admin-card-heading">
            <div>
                <h2 id="edit-user-heading" class="admin-card-title">{{ $user->name }}</h2>
                <p class="admin-card-subtitle">{{ $user->email }}</p>
            </div>
        </div>

        @include('admin.users._form', [
            'isEdit' => true,
            'userRecord' => $user,
            'roles' => $roles,
        ])
    </section>
</div>
@endsection
