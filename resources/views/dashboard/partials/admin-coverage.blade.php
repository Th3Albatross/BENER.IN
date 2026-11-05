{{--
    Kelengkapan data: how much of the master data is actually usable. Each row
    is a thin track at the true ratio, both ends labelled. A 100% row is quiet
    (ink value, no fill emphasis); a real zero gets no stub.
    Props: $coverage — list<array{label, done, total, pct}>.
--}}
@php $coverage = $coverage ?? []; @endphp

<article class="admin-card admin-health-card" aria-labelledby="adminCoverageTitle">
    <div class="admin-card-heading">
        <div class="admin-card-leading">
            <x-admin-icon name="coverage" class="admin-card-icon" />
            <div>
                <h2 class="admin-card-title" id="adminCoverageTitle">Kelengkapan Data</h2>
                <p class="admin-card-subtitle">Persentase data induk yang sudah dapat dipakai.</p>
            </div>
        </div>
    </div>

    @if (count($coverage) === 0)
        <div class="admin-empty admin-empty--frame"><x-admin-icon name="coverage" class="admin-empty-icon" /><span>Belum ada data induk untuk dinilai.</span></div>
    @else
        <ul class="admin-coverage">
            @foreach ($coverage as $row)
                @php $complete = $row['total'] > 0 && $row['pct'] >= 100; @endphp
                <li class="admin-coverage-item {{ $complete ? 'admin-coverage-item--complete' : '' }}">
                    <span class="admin-coverage-label">{{ $row['label'] }}</span>
                    <span class="admin-coverage-track" role="img" aria-label="{{ $row['pct'] }} persen">
                        <span class="admin-coverage-fill" style="width: {{ $row['pct'] }}%"></span>
                    </span>
                    <span class="admin-coverage-value">
                        {{ number_format($row['done'], 0, ',', '.') }}/{{ number_format($row['total'], 0, ',', '.') }}
                        &middot; {{ $row['pct'] }}%
                    </span>
                </li>
            @endforeach
        </ul>
    @endif
</article>
