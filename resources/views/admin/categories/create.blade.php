@extends('layouts.app')

@push('styles')
    @vite('resources/css/admin.css')
@endpush

@section('title', 'Tambah Kategori')
@section('page-title', 'Tambah Kategori')
@section('page-description', 'Tambahkan kategori laporan baru.')

@section('content')
<div class="admin-page">
    <section class="admin-card" aria-labelledby="create-category-heading">
        <div class="admin-card-heading">
            <div>
                <h2 id="create-category-heading" class="admin-card-title">Kategori Baru</h2>
                <p class="admin-card-subtitle">Kategori membantu petugas mengelompokkan laporan.</p>
            </div>
        </div>

        @include('admin.categories._form', [
            'isEdit' => false,
            'categoryRecord' => null,
        ])
    </section>
</div>
@endsection
