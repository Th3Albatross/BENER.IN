@extends('layouts.app')

@push('styles')
    @vite('resources/css/admin.css')
@endpush

@section('title', 'Ubah Kategori')
@section('page-title', 'Ubah Kategori')
@section('page-description', 'Perbarui nama kategori.')

@section('content')
<div class="admin-page">
    <section class="admin-card" aria-labelledby="edit-category-heading">
        <div class="admin-card-heading">
            <div>
                <h2 id="edit-category-heading" class="admin-card-title">{{ $category->name }}</h2>
                <p class="admin-card-subtitle">Digunakan oleh {{ $category->reports()->count() }} laporan.</p>
            </div>
        </div>

        @include('admin.categories._form', [
            'isEdit' => true,
            'categoryRecord' => $category,
        ])
    </section>
</div>
@endsection
