/**
 * Kayan ERP — Odoo-Style Home Applications Launcher Engine
 * Real-time filter, keyboard shortcuts, and responsive app launch
 */
export function initHomePage() {
    const homeContainer = document.querySelector('[data-page="home-apps"]');
    if (!homeContainer) return;

    const searchInput = document.getElementById('homeAppSearch');
    const appsGrid = document.getElementById('appsHubGrid');
    const noResults = document.getElementById('appsNoResults');
    const appCards = appsGrid ? Array.from(appsGrid.querySelectorAll('.app-card')) : [];

    if (!searchInput || appCards.length === 0) return;

    // Filter apps on keystroke
    searchInput.addEventListener('input', () => {
        const query = searchInput.value.trim().toLowerCase();
        let visibleCount = 0;

        appCards.forEach(card => {
            const keywords = (card.getAttribute('data-app-keywords') || '').toLowerCase();
            const title = (card.querySelector('.app-title')?.textContent || '').toLowerCase();
            const desc = (card.querySelector('.app-desc')?.textContent || '').toLowerCase();

            const isMatch = !query || title.includes(query) || desc.includes(query) || keywords.includes(query);

            if (isMatch) {
                card.style.display = '';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });

        if (noResults) {
            if (visibleCount === 0) {
                noResults.classList.remove('hidden');
            } else {
                noResults.classList.add('hidden');
            }
        }
    });

    // Clear search on Escape
    searchInput.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            searchInput.value = '';
            searchInput.dispatchEvent(new Event('input'));
            searchInput.blur();
        } else if (e.key === 'Enter') {
            // Open first visible app card
            const firstVisible = appCards.find(card => card.style.display !== 'none');
            const mainLink = firstVisible?.querySelector('a.app-card-link, a.erp-btn');
            if (mainLink) {
                mainLink.click();
            }
        }
    });
}
