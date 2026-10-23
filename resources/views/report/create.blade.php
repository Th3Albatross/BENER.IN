@extends('layouts.app')

@push('styles')
    @vite('resources/css/pages/report-form.css')
@endpush

@section('title', 'Buat Laporan')
@section('page-title', 'Buat Laporan')
@section('page-description', 'Laporkan kerusakan atau masalah fasilitas sekolah.')

@section('content')
    @include('report._form', [
        'isEdit' => false,
        'reportRecord' => null,
        'formAction' => url('/reports/create'),
        'identifier' => $identifier ?? old('identifier'),
        'existingPhotos' => [],
    ])
@endsection
