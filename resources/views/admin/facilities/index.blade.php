@extends('layouts.app')

@push('styles')
    @vite('resources/css/pages/admin.css')
@endpush

@section('title', 'Kelola Fasilitas')
@section('page-title', 'Fasilitas')
@section('page-description', 'Kelola daftar fasilitas sekolah yang dapat dilaporkan.')

@section('content')
<div class="admin-page">
    @include('admin.partials.flash')

    @if ($errors->has('facility'))
        <div class="admin-message admin-message--error" role="alert">{{ $errors->first('facility') }}</div>
    @endif

    <section class="admin-card" aria-labelledby="facilities-heading">
        <div class="admin-card-heading">
            <div>
                <h2 id="facilities-heading" class="admin-card-title">Daftar Fasilitas</h2>
                <p class="admin-card-subtitle">{{ number_format($facilities->total(), 0, ',', '.') }} fasilitas terdaftar.</p>
            </div>

            <a href="{{ route('admin.facilities.create') }}" class="admin-button admin-button--primary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path d="M12 5v14M5 12h14" />
                </svg>
                <span>Tambah fasilitas</span>
            </a>
        </div>

        @if ($facilities->isEmpty())
            <div class="admin-empty">
                <strong>Belum ada fasilitas</strong>
                Tambahkan fasilitas agar siswa dapat melaporkannya.
            </div>
        @else
            <div class="admin-table-wrapper">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th scope="col">Fasilitas</th>
                            <th scope="col">Jumlah laporan</th>
                            <th scope="col">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($facilities as $facility)
                            <tr>
                                <td data-label="Fasilitas"><span class="admin-table-primary">{{ $facility->name }}</span></td>
                                <td data-label="Jumlah laporan"><span class="admin-count-pill">{{ number_format($facility->reports_count, 0, ',', '.') }}</span></td>
                                <td data-label="Aksi">
                                    <div class="admin-table-actions">
                                        <a href="{{ route('admin.facilities.edit', $facility) }}" class="admin-button admin-button--secondary admin-button--small">Ubah</a>

                                        <form
                                            action="{{ route('admin.facilities.destroy', $facility) }}"
                                            method="POST"
                                            onsubmit="return confirm('Hapus fasilitas {{ $facility->name }}?')"
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

            @include('admin.partials.pagination', ['paginator' => $facilities])
        @endif
    </section>
</div>
@endsection
