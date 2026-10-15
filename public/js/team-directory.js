(() => {
    const cards = [...document.querySelectorAll('[data-team-group]')];
    const filters = [...document.querySelectorAll('[data-team-filter]')];
    const search = document.querySelector('[data-team-search]');
    let selected = 'semua';
    function update() {
        const query = (search?.value ?? '').toLocaleLowerCase('id').trim();
        let count = 0;
        cards.forEach(card => {
            card.hidden = !(selected === 'semua' || card.dataset.teamGroup === selected)
                || !card.dataset.teamText.includes(query);
            if (!card.hidden) count++;
        });
        document.querySelector('[data-team-count]').textContent = `Menampilkan ${count} anggota tim`;
        document.querySelector('[data-team-empty]').hidden = count > 0;
    }
    filters.forEach(button => button.addEventListener('click', () => {
        selected = button.dataset.teamFilter;
        filters.forEach(filter => {
            const active = filter === button;
            filter.classList.toggle('is-active', active);
            filter.setAttribute('aria-pressed', String(active));
        });
        update();
    }));
    search?.addEventListener('input', update);
})();
