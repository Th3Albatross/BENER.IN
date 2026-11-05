{{--
    Shared outline icon set for the admin surface (stroke-width 1.7, matching
    the sidebar and settings vocabulary). The wrapper class is passed in so the
    same glyph can sit in a tinted card-header square or a neutral empty-state
    box. Decorative only: the label beside it always names the thing.

    Usage: <x-admin-icon name="users" class="admin-card-icon" />
--}}
@props(['name'])

<span {{ $attributes->merge(['class' => 'admin-icon']) }} aria-hidden="true">
    <svg viewBox="0 0 24 24" fill="none">
        @switch($name)
            @case('users')
                <circle cx="12" cy="8" r="3.5" />
                <path d="M5 20c.7-3.2 3-5 7-5s6.3 1.8 7 5" />
                @break
            @case('facilities')
                <path d="M4 20V9l8-5 8 5v11" />
                <path d="M9 20v-6h6v6" />
                @break
            @case('categories')
                <rect x="4" y="4" width="7" height="7" rx="1.5" />
                <rect x="13" y="4" width="7" height="7" rx="1.5" />
                <rect x="4" y="13" width="7" height="7" rx="1.5" />
                <rect x="13" y="13" width="7" height="7" rx="1.5" />
                @break
            @case('coverage')
                <path d="M12 3 21 7.5v9L12 21l-9-4.5v-9z" />
                <path d="m8 12 2.5 2.5L16 9" />
                @break
            @case('attention')
                <path d="M12 4 21 20H3z" />
                <path d="M12 10v4M12 17h.01" />
                @break
            @case('check')
                <path d="m5 12 4 4L19 6" />
                @break
            @default
                <circle cx="12" cy="12" r="8.5" />
        @endswitch
    </svg>
</span>
