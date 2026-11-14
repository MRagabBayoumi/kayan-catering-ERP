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
    const appItems = appsGrid ? Array.from(appsGrid.querySelectorAll('.odoo-app-item, .app-card')) : [];

    if (!searchInput || appItems.length === 0) return;

    // Filter apps on keystroke
    searchInput.addEventListener('input', () => {
        const query = searchInput.value.trim().toLowerCase();
        let visibleCount = 0;

        appItems.forEach(item => {
            const appName = (item.getAttribute('data-app-name') || '').toLowerCase();
            const keywords = (item.getAttribute('data-app-keywords') || '').toLowerCase();
            const label = (item.querySelector('.odoo-app-label, .app-title')?.textContent || '').toLowerCase();

            const isMatch = !query || label.includes(query) || appName.includes(query) || keywords.includes(query);

            if (isMatch) {
                item.style.display = '';
                visibleCount++;
            } else {
                item.style.display = 'none';
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

    // Clear search on Escape & Enter to open first
    searchInput.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            searchInput.value = '';
            searchInput.dispatchEvent(new Event('input'));
            searchInput.blur();
        } else if (e.key === 'Enter') {
            const firstVisible = appItems.find(item => item.style.display !== 'none');
            if (firstVisible) {
                firstVisible.click();
            }
        }
    });
}
