import {
    ArcElement,
    BarController,
    BarElement,
    CategoryScale,
    Chart,
    DoughnutController,
    Filler,
    LineController,
    LineElement,
    LinearScale,
    PointElement,
    Tooltip,
} from 'chart.js';

Chart.register(
    ArcElement,
    BarController,
    BarElement,
    CategoryScale,
    DoughnutController,
    Filler,
    LineController,
    LineElement,
    LinearScale,
    PointElement,
    Tooltip,
);

const page = document.querySelector('.analytics-page');

/* ------------------------------------------------------------------ helpers */

const numberFormat = new Intl.NumberFormat('id-ID');

/** Read a CSS custom property from the page so colours live only in analytics.css. */
const token = (name, fallback) => {
    if (!page) return fallback;
    return getComputedStyle(page).getPropertyValue(name).trim() || fallback;
};

const withAlpha = (hex, alpha) => {
    const value = hex.replace('#', '');
    if (value.length !== 6) return hex;
    const [r, g, b] = [0, 2, 4].map((i) => parseInt(value.slice(i, i + 2), 16));
    return `rgba(${r}, ${g}, ${b}, ${alpha})`;
};

const shorten = (text, max = 24) => (text.length > max ? `${text.slice(0, max - 1)}…` : text);

/** Trend labels are "YYYY-MM-DD" (daily) or "YYYY-MM" (monthly). */
const isMonthly = (label) => label.length === 7;

const toDate = (label) => new Date(isMonthly(label) ? `${label}-01T00:00:00Z` : `${label}T00:00:00Z`);

const formatTick = (label) =>
    new Intl.DateTimeFormat('id-ID', isMonthly(label)
        ? { month: 'short', year: 'numeric', timeZone: 'UTC' }
        : { day: 'numeric', month: 'short', timeZone: 'UTC' }).format(toDate(label));

const formatTitle = (label) =>
    new Intl.DateTimeFormat('id-ID', isMonthly(label)
        ? { month: 'long', year: 'numeric', timeZone: 'UTC' }
        : { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric', timeZone: 'UTC' }).format(toDate(label));

const reportsLabel = (value) => `${numberFormat.format(value)} laporan`;

/* ------------------------------------------------------------ filter form */

const initFilterForm = () => {
    const form = document.querySelector('[data-analytics-filter]');
    if (!form) return;

    const select = form.querySelector('select[name="period"]');
    const start = form.querySelector('input[name="start_date"]');
    const end = form.querySelector('input[name="end_date"]');
    if (!select) return;

    const isoDay = (date) => {
        const pad = (n) => String(n).padStart(2, '0');
        return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
    };

    const sync = () => {
        const custom = select.value === 'custom';
        form.dataset.period = select.value;
        // Hidden dates must not leak into the URL of preset periods.
        [start, end].forEach((input) => {
            if (!input) return;
            input.required = custom;
            input.disabled = !custom;
        });
    };

    select.addEventListener('change', () => {
        sync();

        if (select.value !== 'custom') {
            form.requestSubmit();
            return;
        }

        // Pre-fill the last 30 days so the person only has to adjust the range.
        if (start && end && !start.value && !end.value) {
            const today = new Date();
            const from = new Date();
            from.setDate(today.getDate() - 29);
            start.value = isoDay(from);
            end.value = isoDay(today);
        }
    });

    // Keep the range valid while it is being edited.
    start?.addEventListener('change', () => {
        if (end && start.value) end.min = start.value;
    });
    end?.addEventListener('change', () => {
        if (start && end.value) start.max = end.value;
    });
    if (end && start?.value) end.min = start.value;

    sync();
};

const initPrint = () => {
    document.querySelector('[data-analytics-print]')?.addEventListener('click', () => window.print());
};

/* ------------------------------------------------------------------ charts */

const readPayload = () => {
    const node = document.getElementById('analytics-chart-data');
    if (!node) return null;

    try {
        return JSON.parse(node.textContent || '{}');
    } catch (error) {
        console.error('Data analytics tidak dapat dibaca.', error);
        return null;
    }
};

const canvas = (id) => document.getElementById(id);

const baseOptions = () => ({
    responsive: true,
    maintainAspectRatio: false,
    animation: { duration: 450 },
    plugins: {
        legend: { display: false },
        tooltip: {
            backgroundColor: '#26323c',
            titleColor: '#ffffff',
            bodyColor: '#e8edf0',
            padding: 9,
            cornerRadius: 6,
            displayColors: false,
            titleFont: { weight: '600' },
        },
    },
});

const renderTrend = (rows, colors) => {
    const el = canvas('analytics-trend-chart');
    if (!el || !rows.length) return;

    const tag = document.querySelector('[data-analytics-trend-tag]');
    if (tag) tag.textContent = isMonthly(rows[0].label) ? 'Per bulan' : 'Per hari';

    const ctx = el.getContext('2d');
    const gradient = ctx.createLinearGradient(0, 0, 0, el.parentElement.clientHeight || 285);
    gradient.addColorStop(0, withAlpha(colors.brand, 0.22));
    gradient.addColorStop(1, withAlpha(colors.brand, 0));

    new Chart(el, {
        type: 'line',
        data: {
            labels: rows.map((row) => row.label),
            datasets: [{
                data: rows.map((row) => row.total),
                borderColor: colors.brand,
                backgroundColor: gradient,
                borderWidth: 2,
                fill: true,
                tension: 0.35,
                pointRadius: rows.length <= 14 ? 3 : 0,
                pointHoverRadius: 5,
                pointBackgroundColor: '#ffffff',
                pointBorderColor: colors.brand,
                pointBorderWidth: 2,
            }],
        },
        options: {
            ...baseOptions(),
            interaction: { mode: 'index', intersect: false },
            plugins: {
                ...baseOptions().plugins,
                tooltip: {
                    ...baseOptions().plugins.tooltip,
                    callbacks: {
                        title: (items) => formatTitle(rows[items[0].dataIndex].label),
                        label: (item) => reportsLabel(item.parsed.y),
                    },
                },
            },
            scales: {
                x: {
                    grid: { display: false },
                    border: { color: colors.line },
                    ticks: {
                        color: colors.muted,
                        maxRotation: 0,
                        autoSkip: true,
                        maxTicksLimit: 8,
                        callback(value) {
                            return formatTick(this.getLabelForValue(value));
                        },
                    },
                },
                y: {
                    beginAtZero: true,
                    border: { display: false },
                    grid: { color: colors.line },
                    ticks: { color: colors.muted, precision: 0, maxTicksLimit: 5 },
                },
            },
        },
    });
};

/** Doughnut with the total printed in the middle. */
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
        ctx.fillText('laporan', x, y + 12);
        ctx.restore();
    },
});

const renderStatus = (rows, colors) => {
    const el = canvas('analytics-status-chart');
    if (!el || !rows.length) return;

    const names = { Pending: 'Menunggu', Resolved: 'Selesai', Rejected: 'Ditolak' };
    const palette = { Pending: colors.pending, Resolved: colors.resolved, Rejected: colors.rejected };
    const total = rows.reduce((sum, row) => sum + row.total, 0);

    new Chart(el, {
        type: 'doughnut',
        data: {
            labels: rows.map((row) => names[row.label] ?? row.label),
            datasets: [{
                data: rows.map((row) => row.total),
                backgroundColor: rows.map((row) => palette[row.label] ?? colors.brand),
                borderColor: '#ffffff',
                borderWidth: 2,
                hoverOffset: 4,
            }],
        },
        options: {
            ...baseOptions(),
            cutout: '68%',
            plugins: {
                ...baseOptions().plugins,
                tooltip: {
                    ...baseOptions().plugins.tooltip,
                    callbacks: {
                        title: () => '',
                        label: (item) => {
                            const share = total > 0 ? Math.round((item.parsed / total) * 100) : 0;
                            return `${item.label}: ${numberFormat.format(item.parsed)} (${share}%)`;
                        },
                    },
                },
            },
        },
        plugins: [centerTotalPlugin(colors)],
    });
};

/** Severity: fixed order, colour runs from calm blue to a deep rose. */
const renderSeverity = (rows, colors) => {
    const el = canvas('analytics-severity-chart');
    if (!el || !rows.length) return;

    const names = { Low: 'Rendah', Medium: 'Sedang', High: 'Tinggi', Critical: 'Kritis' };
    const palette = { Low: colors.brandLight, Medium: colors.pending, High: colors.high, Critical: colors.critical };

    new Chart(el, {
        type: 'bar',
        data: {
            labels: rows.map((row) => names[row.label] ?? row.label),
            datasets: [{
                data: rows.map((row) => row.total),
                backgroundColor: rows.map((row) => palette[row.label] ?? colors.brand),
                borderRadius: 5,
                borderSkipped: false,
                maxBarThickness: 38,
            }],
        },
        options: {
            ...baseOptions(),
            plugins: {
                ...baseOptions().plugins,
                tooltip: {
                    ...baseOptions().plugins.tooltip,
                    callbacks: {
                        title: (items) => `Keparahan ${items[0].label.toLowerCase()}`,
                        label: (item) => reportsLabel(item.parsed.y),
                    },
                },
            },
            scales: {
                x: { grid: { display: false }, border: { color: colors.line }, ticks: { color: colors.muted } },
                y: {
                    beginAtZero: true,
                    border: { display: false },
                    grid: { color: colors.line },
                    ticks: { color: colors.muted, precision: 0, maxTicksLimit: 5 },
                },
            },
        },
    });
};

/** Horizontal ranking bars, used for categories and facilities. */
const renderRanking = (id, rows, colors) => {
    const el = canvas(id);
    if (!el || !rows.length) return;

    new Chart(el, {
        type: 'bar',
        data: {
            labels: rows.map((row) => row.label),
            datasets: [{
                data: rows.map((row) => row.total),
                backgroundColor: rows.map((_, index) => (index === 0 ? colors.brand : withAlpha(colors.brand, 0.55))),
                borderRadius: 4,
                borderSkipped: false,
                maxBarThickness: 16,
            }],
        },
        options: {
            ...baseOptions(),
            indexAxis: 'y',
            plugins: {
                ...baseOptions().plugins,
                tooltip: {
                    ...baseOptions().plugins.tooltip,
                    callbacks: {
                        title: (items) => rows[items[0].dataIndex].label,
                        label: (item) => reportsLabel(item.parsed.x),
                    },
                },
            },
            scales: {
                x: {
                    beginAtZero: true,
                    border: { display: false },
                    grid: { color: colors.line },
                    ticks: { color: colors.muted, precision: 0, maxTicksLimit: 5 },
                },
                y: {
                    grid: { display: false },
                    border: { display: false },
                    ticks: {
                        color: colors.ink,
                        callback(value) {
                            return shorten(String(this.getLabelForValue(value)));
                        },
                    },
                },
            },
        },
    });
};

/** Mark the page so the person sees a message instead of silent, empty panels. */
const markFailed = () => page?.classList.add('analytics-page--charts-failed');

/** Keep the failure notice in the module instead of adding a second inline script to the view. */
const initChartWatchdog = () => {
    if (!page?.querySelector('canvas')) return;

    window.setTimeout(() => {
        if (!page.dataset.chartsReady) markFailed();
    }, 1500);
};

/** Draw one chart; a failure only affects its own panel and is logged to the console. */
const drawSafely = (canvasId, draw) => {
    try {
        draw();
        return true;
    } catch (error) {
        console.error(`[analytics] Grafik "${canvasId}" gagal dibuat.`, error);
        const host = document.getElementById(canvasId)?.parentElement;
        if (host) {
            host.innerHTML = '<p class="analytics-chart-error">Grafik ini tidak dapat ditampilkan. Detail galat ada di Console browser.</p>';
        }
        return false;
    }
};

const initCharts = () => {
    const payload = readPayload();
    if (!payload) {
        markFailed();
        return false;
    }

    const colors = readColors();

    Chart.defaults.font.family = getComputedStyle(document.body).fontFamily;
    Chart.defaults.font.size = 10;
    Chart.defaults.color = colors.muted;

    // Re-rendering on demand requires clearing the previous instances, otherwise
    // Chart.js reuses them and keeps the colours it captured on first draw.
    destroyCharts();

    const results = [
        drawSafely('analytics-trend-chart', () => renderTrend(payload.trend ?? [], colors)),
        drawSafely('analytics-status-chart', () => renderStatus(payload.statuses ?? [], colors)),
        drawSafely('analytics-severity-chart', () => renderSeverity(payload.severities ?? [], colors)),
        drawSafely('analytics-category-chart', () => renderRanking('analytics-category-chart', payload.categories ?? [], colors)),
        drawSafely('analytics-facility-chart', () => renderRanking('analytics-facility-chart', payload.facilities ?? [], colors)),
    ];

    return results.every(Boolean);
};

/**
 * Read the palette from the active theme tokens.
 *
 * Charts bake these values into the canvas when they are drawn, so printing in
 * dim/dark mode would otherwise emit light strokes on white paper. The print
 * stylesheet forces the light palette, and re-rendering picks up the new values.
 */
const readColors = () => ({
    ink: token('--an-ink', '#26323c'),
    muted: token('--an-muted', '#7b8792'),
    line: token('--an-line', '#e8edf0'),
    brand: token('--an-brand', '#3a5cb8'),
    pending: token('--an-pending', '#e4a54b'),
    resolved: token('--an-resolved', '#3c9c7a'),
    rejected: token('--an-rejected', '#d87980'),
    // Severity scale steps that sit between the existing status colours.
    brandLight: '#8fa8e0',
    high: '#d9825b',
    critical: '#c0545e',
});

const destroyCharts = () => {
    Object.values(Chart.instances).forEach((chart) => chart.destroy());
};

/**
 * Charts are drawn in canvas, so CSS cannot restyle them for print. Re-render
 * before printing (with the print palette) and restore afterwards.
 */
const initPrintRedraw = () => {
    const payload = readPayload();
    if (!payload) return;

    window.addEventListener('beforeprint', () => initCharts());
    window.addEventListener('afterprint', () => initCharts());
};

if (page) {
    initFilterForm();
    initPrint();
    initChartWatchdog();
    initPrintRedraw();

    // The flag proves this module loaded and attempted to render the charts.
    const ok = initCharts();
    page.dataset.chartsReady = ok ? 'true' : 'partial';
}
