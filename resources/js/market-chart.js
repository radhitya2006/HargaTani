import { CategoryScale, Chart, Filler, LineController, LineElement, LinearScale, PointElement, Tooltip } from 'chart.js';

Chart.register(CategoryScale, Filler, LineController, LineElement, LinearScale, PointElement, Tooltip);

const rupiah = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 });
const axisNumber = new Intl.NumberFormat('id-ID');

export function renderMarketChart(canvas) {
    const observations = JSON.parse(canvas.dataset.marketChart);

    new Chart(canvas, {
        type: 'line',
        data: {
            labels: observations.map(({ date }) => date),
            datasets: [{
                data: observations.map(({ price }) => price),
                borderColor: '#166534',
                backgroundColor: '#16653418',
                borderWidth: 2,
                fill: 'start',
                pointRadius: observations.length <= 14 ? 3 : 2,
                pointHoverRadius: 5,
                pointBackgroundColor: '#166534',
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: false,
            interaction: { mode: 'index', intersect: false },
            scales: {
                x: { grid: { display: false }, ticks: { maxTicksLimit: 7, maxRotation: 0, color: '#63705d' } },
                y: {
                    grace: '10%',
                    grid: { color: '#e4e7dc' },
                    ticks: { color: '#63705d', precision: 0, maxTicksLimit: 6, callback: (value) => axisNumber.format(value) },
                },
            },
            plugins: {
                tooltip: { callbacks: { label: ({ parsed }) => `${rupiah.format(parsed.y)} / kg` } },
            },
        },
    });
}
