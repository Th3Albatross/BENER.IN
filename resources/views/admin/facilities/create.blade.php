@extends('layouts.app')

@push('styles')
    @vite('resources/css/pages/admin.css')
@endpush

@section('title', 'Tambah Fasilitas')
@section('page-title', 'Tambah Fasilitas')
@section('page-description', 'Tambahkan fasilitas sekolah baru.')

@section('content')
<div class="admin-page">
    <section class="admin-card" aria-labelledby="create-facility-heading">
        <div class="admin-card-heading">
            <div class="admin-card-leading">
                <x-admin-icon name="facilities" class="admin-card-icon" />
                <div>
                    <h2 id="create-facility-heading" class="admin-card-title">Fasilitas Baru</h2>
                    <p class="admin-card-subtitle">Nama fasilitas akan muncul pada formulir laporan siswa.</p>
                </div>
            </div>
        </div>

        @include('admin.facilities._form', [
            'isEdit' => false,
            'facilityRecord' => null,
        ])
    </section>
</div>
@endsection
