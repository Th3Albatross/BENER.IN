{{--
    Sort control shared by the admin list screens. Matches the filter/sort UI on
    the siswa and petugas dashboards: a labelled <select> with an apply button,
    rather than URL-baked tabs.
    Props: $sort (current value), $routeName, $id (optional unique id).
--}}
@php
    $sort = $sort ?? 'name';
    $id = $id ?? 'admin-sort';
@endphp

<form method="GET" action="{{ route($routeName) }}" class="admin-sort-form">
    <label for="{{ $id }}">Urutkan berdasarkan</label>
    <select id="{{ $id }}" name="sort" class="admin-select admin-select--inline">
        <option value="name" @selected($sort === 'name')>Nama</option>
        <option value="reports" @selected($sort === 'reports')>Paling sering dilaporkan</option>
    </select>
    <button type="submit" class="admin-button admin-button--neutral admin-button--small">Terapkan</button>
</form>
