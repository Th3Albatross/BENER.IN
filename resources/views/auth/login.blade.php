<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#ffffff">
    <title>Login — BENER.IN</title>

    @vite('resources/css/auth-login.css')
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
                    <div class="eyebrow">Student portal</div>
                    <h1 id="login-title">Login</h1>
                    <p class="intro-copy">
                        Use your student credentials to continue.
                    </p>

                    <div class="hero-card" aria-hidden="true">
                        <div class="hero-glow hero-glow-one"></div>
                        <div class="hero-glow hero-glow-two"></div>
                        <div class="hero-pattern"></div>

                        <div class="hero-content">
                            <span class="hero-chip">BENER.IN</span>
                            <div class="hero-title">Your campus,<br>in one place.</div>
                            <p class="hero-subtitle">Simple access to your student services.</p>
                        </div>

                        <div class="hero-orbit hero-orbit-one"></div>
                        <div class="hero-orbit hero-orbit-two"></div>
                        <div class="hero-orbit hero-orbit-three"></div>
                    </div>
                </section>

                <section class="form-panel" aria-labelledby="welcome-title">
                    <div class="form-heading">
                        <div class="form-kicker">Account access</div>
                        <h2 id="welcome-title">Welcome back!</h2>
                        <p>Sign in with your student credentials.</p>
                    </div>

                    <form method="POST" action="#" class="login-form">
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
                                    placeholder="e.g., 2024-000123"
                                    autocomplete="username"
                                    required
                                >
                            </div>
                            @error('email')
                                <p class="field-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="field-group">
                            <div class="field-label-row">
                                <label for="password">Password</label>
                                <a href="#" class="inline-link">Forgot password?</a>
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
                                    placeholder="Enter your password"
                                    autocomplete="current-password"
                                    required
                                >
                                <button class="password-toggle" type="button" data-password-toggle aria-label="Show password">
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
                            @error('password')
                                <p class="field-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="form-meta">
                            <label class="remember-option">
                                <input type="checkbox" name="remember" value="1">
                                <span class="custom-checkbox" aria-hidden="true"></span>
                                <span>Remember me</span>
                            </label>
                        </div>

                        <div class="form-actions">
                            <a href="#" class="secondary-action">Forgot password</a>
                            <button type="submit" class="primary-action">
                                <span>Submit</span>
                                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M5 12h13M13 6l6 6-6 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </button>
                        </div>
                    </form>

                    <p class="form-note">
                        By continuing, you agree to use your assigned student account.
                    </p>
                </section>
            </div>
        </section>

        <footer class="site-footer">
            <span>© {{ date('Y') }} BENER.IN</span>
            <span class="footer-separator"></span>
            <span>Student access portal</span>
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
                toggle.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
                toggle.classList.toggle('is-visible', isPassword);
            });
        });
    </script>
</body>
</html>
