const chart = document.querySelector('[data-market-chart]');

if (chart) {
    import('./market-chart.js')
        .then(({ renderMarketChart }) => renderMarketChart(chart))
        .catch(() => {
            chart.hidden = true;
            document.querySelector('#market-chart-fallback').hidden = false;
        });
}
