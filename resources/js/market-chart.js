import { CategoryScale, Chart, Filler, LineController, LineElement, LinearScale, PointElement, Tooltip } from 'chart.js';

Chart.register(CategoryScale, Filler, LineController, LineElement, LinearScale, PointElement, Tooltip);

const rupiah = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 });
const axisNumber = new Intl.NumberFormat('id-ID');

function chartColors(canvas) {
    const styles = getComputedStyle(canvas);

    return {
        line: styles.getPropertyValue('--color-primary').trim(),
        fill: styles.getPropertyValue('--market-chart-fill').trim(),
        ticks: styles.getPropertyValue('--muted').trim(),
        grid: styles.getPropertyValue('--color-line').trim(),
    };
}

export function renderMarketChart(canvas) {
    const observations = JSON.parse(canvas.dataset.marketChart);
    const colors = chartColors(canvas);

    const chart = new Chart(canvas, {
        type: 'line',
        data: {
            labels: observations.map(({ date }) => date),
            datasets: [{
                data: observations.map(({ price }) => price),
                borderColor: colors.line,
                backgroundColor: colors.fill,
                borderWidth: 2,
                fill: 'start',
                pointRadius: observations.length <= 14 ? 3 : 2,
                pointHoverRadius: 5,
                pointBackgroundColor: colors.line,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: false,
            interaction: { mode: 'index', intersect: false },
            scales: {
                x: { grid: { display: false }, ticks: { maxTicksLimit: 7, maxRotation: 0, color: colors.ticks } },
                y: {
                    grace: '10%',
                    grid: { color: colors.grid },
                    ticks: { color: colors.ticks, precision: 0, maxTicksLimit: 6, callback: (value) => axisNumber.format(value) },
                },
            },
            plugins: {
                tooltip: { callbacks: { label: ({ parsed }) => `${rupiah.format(parsed.y)} / kg` } },
            },
        },
    });

    new MutationObserver((_, observer) => {
        if (!canvas.isConnected) {
            observer.disconnect();
            return;
        }

        const colors = chartColors(canvas);
        const [dataset] = chart.data.datasets;
        dataset.borderColor = colors.line;
        dataset.pointBackgroundColor = colors.line;
        dataset.backgroundColor = colors.fill;
        chart.options.scales.x.ticks.color = colors.ticks;
        chart.options.scales.y.ticks.color = colors.ticks;
        chart.options.scales.y.grid.color = colors.grid;
        chart.update('none');
    }).observe(document.documentElement, { attributeFilter: ['class'] });
}
