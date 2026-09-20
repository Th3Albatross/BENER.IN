@extends('layouts.app')

@section('title', 'Dashboard Petugas')
@section('page-title', 'Dashboard')
@section('page-description', 'Facility Status & Maintenance Reports')

@section('content')
    {{--
        Isi widget dashboard petugas diletakkan di sini.
        Layout sidebar tidak perlu ditulis ulang.

        Contoh:
        <section class="widget-card">...</section>
    --}}

    <section class="empty-dashboard" aria-labelledby="dashboard-welcome">
        <div class="empty-dashboard__icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                <rect x="4" y="4" width="16" height="16" rx="2"></rect>
                <path d="M8 9h8M8 13h5M8 17h8"></path>
            </svg>
        </div>
        <h2 id="dashboard-welcome">Dashboard Petugas</h2>
        <p>Widget utama dashboard akan dirender di area <code>@yield('content')</code>.</p>
    </section>
@endsection
