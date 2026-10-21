(() => {
    const toggle = document.getElementById('sidebar-toggle');
    if (toggle) {
        let collapsed = window.matchMedia('(max-width: 1100px)').matches;
        try { const saved = localStorage.getItem('watchpost.sidebar'); if (saved !== null) collapsed = saved === 'collapsed'; } catch (_) {}
        const apply = () => {
            document.body.classList.toggle('sidebar-collapsed', collapsed);
            toggle.setAttribute('aria-expanded', String(!collapsed));
            toggle.setAttribute('aria-label', collapsed ? 'Expand navigation' : 'Collapse navigation');
            toggle.title = collapsed ? 'Expand navigation' : 'Collapse navigation';
            toggle.querySelector('[aria-hidden]').textContent = collapsed ? '⇥' : '⇤';
        };
        apply();
        toggle.addEventListener('click', () => { collapsed = !collapsed; apply(); try { localStorage.setItem('watchpost.sidebar', collapsed ? 'collapsed' : 'expanded'); } catch (_) {} });
    }
    const filters = document.querySelector('.pill-filter-panel');
    if (filters) {
        filters.classList.add('auto-submit');
        filters.addEventListener('change', (event) => { if (event.target.matches('input[type="radio"]')) filters.requestSubmit(); });
    }
    const rangeSelect = document.getElementById('range-select');
    const dateFields = document.getElementById('custom-date-fields');
    if (rangeSelect && dateFields) {
        rangeSelect.addEventListener('change', () => {
            const custom = rangeSelect.value === 'custom';
            dateFields.hidden = !custom;
            if (!custom) {
                rangeSelect.form.requestSubmit();
            }
        });
    }
})();
