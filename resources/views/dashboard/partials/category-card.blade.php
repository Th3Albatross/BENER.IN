<div class="dashboard-card category-card">
    <div class="dashboard-card-header">
        <div>
            <h2 class="dashboard-card-title">Laporan per Kategori</h2>
            <p class="dashboard-card-subtitle">{{ $_isStaff ? 'Kategori dari seluruh laporan' : 'Kategori yang paling sering dipilih' }}</p>
        </div>
    </div>
    <div class="widget-list-viewport category-viewport">
        @if ($_categoryStats->isNotEmpty())
            <div class="category-list">
                @foreach ($_categoryStats as $categoryStat)
                    <div class="category-item">
                        <div class="category-info">
                            <span title="{{ $categoryStat->category_name }}">{{ $categoryStat->category_name }}</span>
                            <strong>{{ $categoryStat->total }}</strong>
                        </div>
                        <div class="category-progress" role="progressbar" aria-label="Jumlah laporan kategori {{ $categoryStat->category_name }}" aria-valuemin="0" aria-valuemax="{{ $_maxCategoryReports }}" aria-valuenow="{{ $categoryStat->total }}">
                            <div class="category-progress-fill" style="width: {{ max(5, (int) round(($categoryStat->total / $_maxCategoryReports) * 100)) }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <p class="widget-empty">Belum ada data kategori untuk ditampilkan.</p>
        @endif
    </div>
</div>
