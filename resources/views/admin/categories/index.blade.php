@extends('layouts.app')

@push('styles')
    @vite('resources/css/pages/admin.css')
@endpush

@section('title', 'Kelola Kategori')
@section('page-title', 'Kategori')
@section('page-description', 'Kelola kategori laporan fasilitas.')

@section('content')
<div class="admin-page">
    @include('admin.partials.flash')

    @if ($errors->has('category'))
        <div class="admin-message admin-message--error" role="alert">{{ $errors->first('category') }}</div>
    @endif

    <section class="admin-card" aria-labelledby="categories-heading">
        <div class="admin-card-heading">
            <div class="admin-card-leading">
                <x-admin-icon name="categories" class="admin-card-icon" />
                <div>
                    <h2 id="categories-heading" class="admin-card-title">Daftar Kategori</h2>
                    @php
                        $unused = (int) ($unusedCount ?? 0);
                        $subtitle = number_format($categories->total(), 0, ',', '.').' kategori terdaftar';
                    @endphp
                    <p class="admin-card-subtitle">
                        @if ($unused > 0)
                            {{ $subtitle }} &middot; <strong>{{ number_format($unused, 0, ',', '.') }}</strong> belum pernah dilaporkan.
                        @else
                            {{ $subtitle }}.
                        @endif
                    </p>
                </div>
            </div>

            <a href="{{ route('admin.categories.create') }}" class="admin-button admin-button--primary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path d="M12 5v14M5 12h14" />
                </svg>
                <span>Tambah kategori</span>
            </a>
        </div>

        @include('admin.partials.sort-form', [
            'sort' => $sort ?? 'name',
            'routeName' => 'admin.categories.index',
            'id' => 'categories-sort',
        ])

        @if ($categories->isEmpty())
            <div class="admin-empty admin-empty--frame">
                <x-admin-icon name="categories" class="admin-empty-icon" />
                <strong>Belum ada kategori</strong>
                <span>Tambahkan kategori agar laporan lebih mudah dikelompokkan.</span>
            </div>
        @else
            <div class="admin-table-wrapper">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th scope="col">Kategori</th>
                            <th scope="col" class="num">Jumlah laporan</th>
                            <th scope="col" class="num">Pemakaian</th>
                            <th scope="col">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($categories as $category)
                            <tr>
                                <td data-label="Kategori">
                                    <span class="admin-table-primary">{{ $category->name }}</span>
                                    @if ($category->reports_count === 0)
                                        <span class="admin-table-secondary">Belum pernah dilaporkan</span>
                                    @endif
                                </td>
                                <td data-label="Jumlah laporan" class="num"><span class="admin-count-pill">{{ number_format($category->reports_count, 0, ',', '.') }}</span></td>
                                <td data-label="Pemakaian" class="num">
                                    @include('admin.partials.usage-bar', [
                                        'value' => $category->reports_count,
                                        'max' => $maxReports ?? 1,
                                        'label' => 'laporan',
                                    ])
                                </td>
                                <td data-label="Aksi">
                                    <div class="admin-table-actions">
                                        <a href="{{ route('admin.categories.edit', $category) }}" class="admin-button admin-button--secondary admin-button--small">Ubah</a>

                                        <form
                                            action="{{ route('admin.categories.destroy', $category) }}"
                                            method="POST"
                                            onsubmit="return confirm('Hapus kategori {{ $category->name }}?')"
                                        >
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="admin-button admin-button--danger admin-button--small">Hapus</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @include('admin.partials.pagination', ['paginator' => $categories])
        @endif
    </section>
</div>
@endsection
