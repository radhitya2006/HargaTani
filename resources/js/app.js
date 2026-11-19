const chart = document.querySelector('[data-market-chart]');

if (chart) {
    import('./market-chart.js')
        .then(({ renderMarketChart }) => renderMarketChart(chart))
        .catch(() => {
            chart.hidden = true;
            document.querySelector('#market-chart-fallback').hidden = false;
        });
}

if ('IntersectionObserver' in window) {
    const revealObserver = new IntersectionObserver((entries) => {
        for (const { isIntersecting, target } of entries) {
            if (isIntersecting) {
                target.dataset.scrollReveal = 'revealed';
                revealObserver.unobserve(target);
            }
        }
    });

    function observeRevealSections() {
        revealObserver.disconnect();

        for (const section of document.querySelectorAll('[data-scroll-reveal]:not([data-scroll-reveal="revealed"])')) {
            if (section.getBoundingClientRect().top >= window.innerHeight) {
                section.dataset.scrollReveal = 'pending';
                revealObserver.observe(section);
            } else {
                section.dataset.scrollReveal = 'revealed';
            }
        }
    }

    observeRevealSections();
    document.addEventListener('livewire:navigated', observeRevealSections);
}
