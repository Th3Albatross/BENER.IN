<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#f2f3f5">
    <title>Masuk — BENER.IN</title>

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
    @vite('resources/css/pages/auth-login.css')
</head>
<body class="login-page">
    <main class="login-shell">
        <header class="site-header">
            <a href="{{ url('/') }}" class="brand" aria-label="BENER.IN">
                <span class="brand-mark" aria-hidden="true">
                    <span class="brand-mark-dot"></span>
                </span>
                <span class="brand-name">BENER.IN</span>
            </a>
        </header>

        <section class="login-content">
            <div class="login-grid">
                <section class="intro-panel" aria-labelledby="login-title">
                    <div class="eyebrow">Portal Fasilitas Sekolah</div>
                    <h1 id="login-title">Masuk</h1>
                    <p class="intro-copy">
                        Gunakan akunmu untuk melanjutkan.
                    </p>

                    <div class="hero-card" aria-hidden="true">
                        <div class="hero-glow hero-glow-one"></div>
                        <div class="hero-glow hero-glow-two"></div>
                        <div class="hero-pattern"></div>

                        <div class="hero-content">
                            <span class="hero-chip">BENER.IN</span>
                            <div class="hero-title">Laporkan fasilitas,<br>dalam satu tempat.</div>
                            <p class="hero-subtitle">Akses mudah ke layanan pelaporan fasilitas sekolah.</p>
                        </div>

                        <div class="hero-orbit hero-orbit-one"></div>
                        <div class="hero-orbit hero-orbit-two"></div>
                        <div class="hero-orbit hero-orbit-three"></div>
                    </div>
                </section>

                <section class="form-panel" aria-labelledby="welcome-title">
                    <div class="form-heading">
                        <div class="form-kicker">Akses Akun</div>
                        <h2 id="welcome-title">Selamat datang kembali!</h2>
                        <p>Masuk menggunakan akunmu.</p>
                    </div>

                    <form method="POST" action="{{ route('login') }}" class="login-form">
                        @csrf

                        <div class="field-group">
                            <label for="email">Email</label>
                            <div class="input-wrap">
                                <svg class="input-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M4 6.75h16v10.5H4V6.75Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>
                                    <path d="m5 8 7 5 7-5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                <input
                                    id="email"
                                    type="email"
                                    name="email"
                                    value="{{ old('email') }}"
                                    placeholder="Masukkan email kamu"
                                    autocomplete="username"
                                    required
                                >
                            </div>
                        </div>

                        <div class="field-group">
                            <div class="field-label-row">
                                <label for="password">Kata Sandi</label>
                            </div>

                            <div class="input-wrap">
                                <svg class="input-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <rect x="5" y="10" width="14" height="10" rx="2" stroke="currentColor" stroke-width="1.7"/>
                                    <path d="M8 10V7.75A4 4 0 0 1 12 4a4 4 0 0 1 4 3.75V10" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                                </svg>
                                <input
                                    id="password"
                                    type="password"
                                    name="password"
                                    placeholder="Masukkan kata sandi kamu"
                                    autocomplete="current-password"
                                    required
                                >
                                <button class="password-toggle" type="button" data-password-toggle aria-label="Tampilkan kata sandi">
                                    <svg class="eye-icon eye-icon-open" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <path d="M2.8 12s3.25-5 9.2-5 9.2 5 9.2 5-3.25 5-9.2 5-9.2-5-9.2-5Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>
                                        <circle cx="12" cy="12" r="2.3" stroke="currentColor" stroke-width="1.7"/>
                                    </svg>
                                    <svg class="eye-icon eye-icon-closed" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <path d="m3.25 3.25 17.5 17.5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                                        <path d="M9.85 9.85a3 3 0 0 0 4.3 4.3M6.8 6.95C4.25 8.33 2.8 12 2.8 12s3.25 5 9.2 5c1.72 0 3.2-.4 4.43-1" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M13.1 7.2c4.45.5 8.1 4.8 8.1 4.8s-1.4 2.17-3.82 3.57" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                                    </svg>
                                </button>
                            </div>
                            @error('loginFailure')
                                <p class="field-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="form-meta">
                            <label class="remember-option">
                                <input type="checkbox" name="remember" value="1">
                                <span class="custom-checkbox" aria-hidden="true"></span>
                                <span>Ingat saya</span>
                            </label>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="primary-action">
                                <span>Masuk</span>
                                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M5 12h13M13 6l6 6-6 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </button>
                        </div>
                    </form>

                    <p class="form-note">
                        Dengan melanjutkan, kamu setuju menggunakan akun yang telah diberikan kepadamu.
                    </p>
                </section>
            </div>
        </section>

        <footer class="site-footer">
            <span>© {{ date('Y') }} BENER.IN</span>
            <span class="footer-separator"></span>
            <span>Portal fasilitas sekolah</span>
        </footer>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const toggle = document.querySelector('[data-password-toggle]');
            const password = document.getElementById('password');

            if (!toggle || !password) return;

            toggle.addEventListener('click', () => {
                const isPassword = password.type === 'password';
                password.type = isPassword ? 'text' : 'password';
                toggle.setAttribute('aria-label', isPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi');
                toggle.classList.toggle('is-visible', isPassword);
            });
        });
    </script>
</body>
</html>
