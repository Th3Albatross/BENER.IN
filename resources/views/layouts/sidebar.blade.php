<aside class="sidebar" aria-label="Navigasi utama">
    <div class="sidebar__top">
        <a href="{{ url('/dashboard') }}" class="brand" aria-label="BENER.IN Dashboard">
            <span class="brand__mark" aria-hidden="true">
                <span class="brand__mark-inner"></span>
            </span>
            <span class="brand__name">BENER.IN</span>
        </a>

        <nav class="sidebar__nav">
            <a href="{{ url('/dashboard') }}"
               class="nav-item {{ request()->is('dashboard') ? 'nav-item--active' : '' }}">
                <span class="nav-item__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                        <rect x="4" y="4" width="16" height="16" rx="2"></rect>
                        <path d="M8 9h8M8 13h5M8 17h8"></path>
                    </svg>
                </span>
                <span>Dashboard</span>
            </a>

            <a href="{{ url('/reports/create') }}"
               class="nav-item {{ request()->is('reports/create') ? 'nav-item--active' : '' }}">
                <span class="nav-item__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                        <path d="M12 5v14M5 12h14"></path>
                    </svg>
                </span>
                <span>New Report</span>
            </a>

            <a href="{{ url('/settings') }}"
               class="nav-item {{ request()->is('settings') ? 'nav-item--active' : '' }}">
                <span class="nav-item__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                        <path d="M12 8.5a3.5 3.5 0 1 0 0 7 3.5 3.5 0 0 0 0-7Z"></path>
                        <path d="m19 12 1.3 1-.9 1.9-1.6-.1a6.7 6.7 0 0 1-1.2 1.2l.1 1.6-1.9.9-1-1.3a6.9 6.9 0 0 1-1.8 0l-1 1.3-1.9-.9.1-1.6a6.7 6.7 0 0 1-1.2-1.2l-1.6.1L5.5 13l1.3-1a6.9 6.9 0 0 1 0-1.8l-1.3-1 .9-1.9 1.6.1a6.7 6.7 0 0 1 1.2-1.2L9.1 4.6l1.9-.9 1 1.3a6.9 6.9 0 0 1 1.8 0l1-1.3 1.9.9-.1 1.6a6.7 6.7 0 0 1 1.2 1.2l1.6-.1.9 1.9-1.3 1a6.9 6.9 0 0 1 0 1.8Z"></path>
                    </svg>
                </span>
                <span>Settings</span>
            </a>
        </nav>
    </div>

    <div class="sidebar__user">
        <div class="user-avatar" aria-hidden="true">
            {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
        </div>

        <div class="user-info">
            <div class="user-info__name">
                {{ auth()->user()->name ?? 'User' }}
            </div>
            <div class="user-info__role">
                {{ auth()->user()->role }}
            </div>
        </div>
    </div>
</aside>
