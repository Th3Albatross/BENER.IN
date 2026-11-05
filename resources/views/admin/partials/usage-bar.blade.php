{{--
    A thin proportional usage bar + the raw count, shared by Fasilitas/Kategori.
    The bar gives the ranking a visible form; the count stays in ink so a real
    zero reads as a real zero (skill §6.4: no stub, no greyed row).
    Props: $value (int), $max (int >= 1), $label (string, aria text).
--}}
@php
    $value = (int) ($value ?? 0);
    $max = max(1, (int) ($max ?? 1));
    $label = $label ?? 'Jumlah laporan';
    $pct = $value > 0 ? max(2, (int) round(($value / $max) * 100)) : 0;
@endphp

<span class="admin-usage">
    <span class="admin-usage-track" role="img" aria-label="{{ $value }} {{ $label }}">
        <span class="admin-usage-fill" style="width: {{ $pct }}%"></span>
    </span>
    <span class="admin-usage-value">{{ number_format($value, 0, ',', '.') }}</span>
</span>
