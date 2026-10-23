@extends('layouts.app')

@push('styles')
    @vite('resources/css/pages/admin.css')
@endpush

@section('title', 'Ubah Fasilitas')
@section('page-title', 'Ubah Fasilitas')
@section('page-description', 'Perbarui nama fasilitas.')

@section('content')
<div class="admin-page">
    <section class="admin-card" aria-labelledby="edit-facility-heading">
        <div class="admin-card-heading">
            <div>
                <h2 id="edit-facility-heading" class="admin-card-title">{{ $facility->name }}</h2>
                <p class="admin-card-subtitle">Digunakan oleh {{ $facility->reports()->count() }} laporan.</p>
            </div>
        </div>

        @include('admin.facilities._form', [
            'isEdit' => true,
            'facilityRecord' => $facility,
        ])
    </section>
</div>
@endsection
