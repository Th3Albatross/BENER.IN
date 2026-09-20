<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>@yield('title', 'Dashboard') - BENER.IN</title>
    <meta name="description" content="@yield('meta_description', 'BENER.IN facility reporting dashboard')">

    @vite('resources/css/layouts/app.css')
    @stack('styles')
</head>
<body>
    <div class="app-shell">
        @include('layouts.sidebar')

        <main class="main-content">
            <header class="page-header">
                <div class="page-header__copy">
                    <h1>@yield('page-title', 'Dashboard')</h1>
                    <p>@yield('page-description', 'Facility Status & Maintenance Reports')</p>
                </div>

                <div class="page-header__actions">
                    @yield('header-actions')
                </div>
            </header>

            <section class="page-content">
                @yield('content')
            </section>
        </main>
    </div>

    @stack('scripts')
</body>
</html>
