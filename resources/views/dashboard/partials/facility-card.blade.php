<div class="dashboard-card facilities-card">
    <div class="dashboard-card-header">
        <div>
            <h2 class="dashboard-card-title">Fasilitas Paling Sering Dilaporkan</h2>
            <p class="dashboard-card-subtitle">{{ $_isStaff ? 'Berdasarkan laporan seluruh siswa' : 'Berdasarkan laporan yang kamu buat' }}</p>
        </div>
    </div>
    <div class="widget-list-viewport facility-viewport">
        @if ($_facilityStats->isNotEmpty())
            <div class="facility-list">
                @foreach ($_facilityStats as $index => $facilityStat)
                    <div class="facility-item">
                        <div class="facility-rank">{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}</div>
                        <div class="facility-details">
                            <span class="facility-title" title="{{ $facilityStat->facility_name }}">{{ $facilityStat->facility_name }}</span>
                            <span class="facility-count">{{ $facilityStat->total }} laporan</span>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <p class="widget-empty">Belum ada data fasilitas untuk ditampilkan.</p>
        @endif
    </div>
</div>
