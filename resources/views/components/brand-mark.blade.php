{{--
    BENER.IN brand mark.

    A white wrench + screwdriver glyph on the brand-blue rounded square. This is
    hand-authored inline SVG rather than a stored image: it is public, static and
    identical for every visitor, so it should not go through AttendantController
    (which serves private, per-user uploads with no-store caching).

    Inline SVG also means no extra HTTP request and crisp rendering at any size.
    It is decorative: the surrounding link carries the accessible name.
--}}
<svg
    {{ $attributes->merge(['class' => 'brand-mark-svg']) }}
    viewBox="0 0 48 48"
    role="presentation"
    aria-hidden="true"
    focusable="false"
>
    <rect width="48" height="48" rx="11" fill="#3a5cb8" />
    <g fill="#ffffff">
        {{-- Wrench: open jaw head plus a rounded handle. --}}
        <path d="M11.41 9.45 A8 8 0 1 0 20.59 9.45 L18.29 12.72 A4 4 0 1 1 13.71 12.72 Z" />
        <rect x="13.7" y="21" width="4.6" height="18" rx="2.3" />

        {{-- Screwdriver: tapered tip, shaft, and rounded handle. --}}
        <rect x="30.7" y="9" width="2.6" height="18" rx="1.3" />
        <path d="M30.2 8.2 h3.6 l-0.7 2.6 h-2.2 z" />
        <rect x="28.2" y="26.5" width="8.1" height="12.5" rx="2.6" />
    </g>
</svg>
