{{--
    Toolbar shared by the admin list screens: optional filter tabs (links, so
    they work without script and survive paging) and an optional search field.
    The active tab carries aria-current; counts follow the current search.
    Props: $tabs (list<array{value,label,count,href,active}>), $search (string),
           $searchAction (url), $searchPlaceholder (string), $extra (html slot).
--}}
@php
    $tabs = $tabs ?? [];
    $search = $search ?? '';
    $showSearch = $showSearch ?? true;
    $searchAction = $searchAction ?? url()->current();
    $searchPlaceholder = $searchPlaceholder ?? 'Cari...';
@endphp

<div class="admin-toolbar-row">
    @if (count($tabs) > 0)
        <nav class="admin-tabs" aria-label="Saring daftar">
            @foreach ($tabs as $tab)
                <a
                    href="{{ $tab['href'] }}"
                    class="admin-tab {{ $tab['active'] ? 'admin-tab--active' : '' }}"
                    @if ($tab['active']) aria-current="true" @endif
                >
                    {{ $tab['label'] }}
                    <span class="admin-tab-count">{{ number_format($tab['count'], 0, ',', '.') }}</span>
                </a>
            @endforeach
        </nav>
    @endif

    @if ($showSearch)
    <form class="admin-search-form" method="GET" action="{{ $searchAction }}" role="search">
        @foreach ($hiddenQuery ?? [] as $name => $value)
            <input type="hidden" name="{{ $name }}" value="{{ $value }}">
        @endforeach
        <label class="admin-search-label" for="admin-list-search">Cari</label>
        <input
            class="admin-search"
            type="search"
            id="admin-list-search"
            name="q"
            value="{{ $search }}"
            placeholder="{{ $searchPlaceholder }}"
            autocomplete="off"
        >
        <button type="submit" class="admin-button admin-button--secondary admin-button--small">Cari</button>
        @if ($search !== '')
            <a href="{{ $searchAction }}" class="admin-search-clear">Bersihkan</a>
        @endif
    </form>
    @endif
</div>
