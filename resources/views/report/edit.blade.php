@extends('layouts.app')

@push('styles')
    @vite('resources/css/pages/report-form.css')
@endpush

@section('title', 'Edit Laporan')
@section('page-title', 'Edit Laporan')
@section('page-description', 'Perbarui informasi laporan fasilitas yang masih dapat diedit.')

@section('content')
    @php
        $reportRecord = $report ?? ($_report ?? null);
        $existingPhotos = $existingPhotos ?? ($photos ?? ($_photos ?? []));
    @endphp

    @if (!$reportRecord)
        <div class="report-create-widget">
            <div class="report-alert report-alert--error" role="alert">
                <strong>Data laporan untuk diedit belum tersedia.</strong>
                <span>Pastikan controller mengirim objek laporan ke view ini.</span>
            </div>
        </div>
    @else
        @include('report._form', [
            'isEdit' => true,
            'reportRecord' => $reportRecord,
            'formAction' => url('/reports/' . $reportRecord->identifier),
            'existingPhotos' => is_array($existingPhotos) ? $existingPhotos : [],
        ])
    @endif
@endsection
