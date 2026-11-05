{{--
    Perlu perhatian: the screen's finding as a short list, one action per row so
    the fix is one click from the answer. Capped at 5 rows so it ends level with
    the coverage card beside it (skill: ragged-row).
    Props: $dataGaps — list<array{key, label, count, url, cta}>.
--}}
@php $dataGaps = $dataGaps ?? []; @endphp

<article class="admin-card admin-health-card" aria-labelledby="adminAttentionTitle">
    <div class="admin-card-heading">
        <div class="admin-card-leading">
            <x-admin-icon name="attention" class="admin-card-icon admin-card-icon--attention" />
            <div>
                <h2 class="admin-card-title" id="adminAttentionTitle">Perlu Perhatian</h2>
                <p class="admin-card-subtitle">Data induk yang belum lengkap atau belum terpakai.</p>
            </div>
        </div>
    </div>

    @if (count($dataGaps) === 0)
        <div class="admin-empty admin-empty--frame"><x-admin-icon name="check" class="admin-empty-icon" /><span>Semua data induk sudah lengkap dan terpakai.</span></div>
    @else
        <ul class="admin-attention">
            @foreach ($dataGaps as $gap)
                <li>
                    <a href="{{ $gap['url'] }}" class="admin-attention-item">
                        <span class="admin-attention-count">{{ number_format($gap['count'], 0, ',', '.') }}</span>
                        <span class="admin-attention-label">{{ $gap['label'] }}</span>
                        <span class="admin-attention-cta">{{ $gap['cta'] }} <span aria-hidden="true">&rarr;</span></span>
                    </a>
                </li>
            @endforeach
        </ul>
    @endif
</article>
