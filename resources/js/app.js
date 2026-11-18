const menu = document.querySelector('#ht-menu');
const trigger = document.querySelector('#ht-menu-trigger');

if (menu && trigger) {
    const links = [...document.querySelectorAll('.ht-nav-link')];

    const markCurrentLink = () => {
        const { pathname, hash } = window.location;
        const hasSectionLink = hash && links.some((link) => link.pathname === pathname && link.hash === hash);

        for (const link of links) {
            if (link.pathname === pathname && (hasSectionLink ? link.hash === hash : !link.hash)) {
                link.setAttribute('aria-current', link.hash ? 'location' : 'page');
            } else {
                link.removeAttribute('aria-current');
            }
        }
    };

    markCurrentLink();
    window.addEventListener('hashchange', markCurrentLink);

    trigger.addEventListener('click', () => {
        menu.showModal();
        trigger.setAttribute('aria-expanded', 'true');
    });

    menu.addEventListener('close', () => {
        trigger.setAttribute('aria-expanded', 'false');
    });

    menu.addEventListener('click', (event) => {
        const { left, right, top, bottom } = menu.getBoundingClientRect();
        const clickedBackdrop = event.target === menu
            && (event.clientX < left || event.clientX >= right || event.clientY < top || event.clientY >= bottom);

        if (clickedBackdrop || event.target.closest('a')) {
            menu.close();
        }
    });

    window.matchMedia('(min-width: 64rem)').addEventListener('change', (event) => {
        if (event.matches && menu.open) {
            menu.close();
        }
    });
}

const chart = document.querySelector('[data-market-chart]');

if (chart) {
    import('./market-chart.js')
        .then(({ renderMarketChart }) => renderMarketChart(chart))
        .catch(() => {
            chart.hidden = true;
            document.querySelector('#market-chart-fallback').hidden = false;
        });
}
