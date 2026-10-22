<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>@yield('title', 'Dashboard') - BENER.IN</title>
    <meta name="description" content="@yield('meta_description', 'BENER.IN facility reporting dashboard')">

    {{-- Apply the stored mode before first paint so the wrong theme never flashes. --}}
    <script>
        (() => {
            try {
                const stored = localStorage.getItem('benerin:theme');
                if (stored === 'light' || stored === 'dim' || stored === 'dark') {
                    document.documentElement.setAttribute('data-theme', stored);
                    return;
                }
                if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                    document.documentElement.setAttribute('data-theme', 'dark');
                }
            } catch (error) {
                /* Storage unavailable; keep the default light mode. */
            }
        })();
    </script>

    @vite('resources/css/theme.css')
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

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const formatter = new Intl.DateTimeFormat('id-ID', {
                day: '2-digit',
                month: 'short',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
            });

            document.querySelectorAll('time[data-local-time]').forEach((element) => {
                const value = element.getAttribute('datetime');
                if (!value) return;

                const date = new Date(value);
                if (Number.isNaN(date.getTime())) return;

                element.textContent = formatter.format(date);
            });
        });
    </script>

    @stack('scripts')
    @vite('resources/js/theme.js')
</body>
</html>
