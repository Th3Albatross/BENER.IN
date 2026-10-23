/**
 * BENER.IN — Admin dashboard charts.
 *
 * Reads the payload embedded by dashboard/admin.blade.php and draws a role
 * doughnut plus two horizontal rankings. Colours are read from the active
 * theme tokens so light/dim/dark all render correctly.
 */
import {
    ArcElement,
    BarController,
    BarElement,
    CategoryScale,
    Chart,
    DoughnutController,
    LinearScale,
    Tooltip,
} from 'chart.js';

Chart.register(
    ArcElement,
    BarController,
    BarElement,
    CategoryScale,
    DoughnutController,
    LinearScale,
    Tooltip,
);

const page = document.querySelector('[data-admin-dashboard]');

const numberFormat = new Intl.NumberFormat('id-ID');

/** Read a CSS custom property from the page so colours live only in CSS. */
const token = (name, fallback) => {
    if (!page) return fallback;
    return getComputedStyle(page).getPropertyValue(name).trim() || fallback;
};

const readPayload = () => {
    const node = document.getElementById('admin-chart-data');
    if (!node) return null;

    try {
        return JSON.parse(node.textContent || '{}');
    } catch (error) {
        console.error('Data dashboard admin tidak dapat dibaca.', error);
        return null;
    }
};

const canvas = (id) => document.getElementById(id);

const baseOptions = (colors) => ({
    responsive: true,
    maintainAspectRatio: false,
    animation: { duration: 450 },
    plugins: {
        legend: { display: false },
        tooltip: {
            backgroundColor: colors.tooltipBg,
            titleColor: colors.tooltipTitle,
            bodyColor: colors.tooltipBody,
            padding: 9,
            cornerRadius: 6,
            displayColors: false,
            titleFont: { weight: '600' },
        },
    },
});

/** Print the total in the middle of the doughnut. */
const centerTotalPlugin = (colors) => ({
    id: 'centerTotal',
    afterDraw(chart) {
        const { ctx, chartArea } = chart;
        if (!chartArea) return;

        const total = chart.data.datasets[0].data.reduce((sum, value) => sum + value, 0);
        const x = (chartArea.left + chartArea.right) / 2;
        const y = (chartArea.top + chartArea.bottom) / 2;
        const family = Chart.defaults.font.family;

        ctx.save();
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillStyle = colors.ink;
        ctx.font = `700 20px ${family}`;
        ctx.fillText(numberFormat.format(total), x, y - 5);
        ctx.fillStyle = colors.muted;
        ctx.font = `500 10px ${family}`;
        ctx.fillText('pengguna', x, y + 12);
        ctx.restore();
    },
});

const renderRoles = (rows, colors) => {
    const el = canvas('admin-role-chart');
    if (!el || !rows.length) return false;

    const palette = {
        Siswa: colors.siswa,
        Petugas: colors.petugas,
        Admin: colors.admin,
    };

    new Chart(el, {
        type: 'doughnut',
        data: {
            labels: rows.map((row) => row.label),
            datasets: [{
                data: rows.map((row) => row.total),
                backgroundColor: rows.map((row) => palette[row.label] ?? colors.muted),
                borderColor: colors.surface,
                borderWidth: 2,
            }],
        },
        options: {
            ...baseOptions(colors),
            cutout: '66%',
        },
        plugins: [centerTotalPlugin(colors)],
    });

    return true;
};

const renderRanking = (id, rows, colors) => {
    const el = canvas(id);
    if (!el || !rows.length) return false;

    new Chart(el, {
        type: 'bar',
        data: {
            labels: rows.map((row) => row.label),
            datasets: [{
                data: rows.map((row) => row.total),
                backgroundColor: colors.brand,
                borderRadius: 5,
                maxBarThickness: 22,
            }],
        },
        options: {
            ...baseOptions(colors),
            indexAxis: 'y',
            scales: {
                x: {
                    beginAtZero: true,
                    border: { display: false },
                    grid: { color: colors.line },
                    ticks: { color: colors.muted, precision: 0, maxTicksLimit: 5 },
                },
                y: {
                    border: { display: false },
                    grid: { display: false },
                    ticks: { color: colors.muted, autoSkip: false },
                },
            },
        },
    });

    return true;
};

/** Draw one chart; a failure only affects its own panel. */
const drawSafely = (id, draw) => {
    try {
        return draw() !== false;
    } catch (error) {
        console.error(`Grafik ${id} gagal ditampilkan.`, error);
        return false;
    }
};

const initCharts = (payload) => {
    const colors = {
        ink: token('--text-strong', '#2e3338'),
        muted: token('--text-muted', '#747f8d'),
        line: token('--border', '#e3e5e8'),
        surface: token('--surface', '#ffffff'),
        brand: token('--accent-fill', '#3a5cb8'),
        siswa: token('--text-soft', '#6b7280'),
        petugas: token('--success', '#26734b'),
        admin: token('--accent', '#3a5cb8'),
        tooltipBg: '#26323c',
        tooltipTitle: '#ffffff',
        tooltipBody: '#e8edf0',
    };

    Chart.defaults.font.family = getComputedStyle(document.body).fontFamily;
    Chart.defaults.font.size = 10;
    Chart.defaults.color = colors.muted;

    const results = [
        drawSafely('admin-role-chart', () => renderRoles(payload.roles ?? [], colors)),
        drawSafely('admin-facility-chart', () => renderRanking('admin-facility-chart', payload.facilities ?? [], colors)),
        drawSafely('admin-category-chart', () => renderRanking('admin-category-chart', payload.categories ?? [], colors)),
    ];

    return results.some(Boolean);
};

if (page) {
    const payload = readPayload();

    if (payload) {
        const ok = initCharts(payload);
        page.dataset.chartsReady = ok ? 'true' : 'partial';
    }
}
