const toggle = document.querySelector('.menu-toggle');
const navigation = document.querySelector('#navigation');
toggle?.addEventListener('click', () => {
    const open = toggle.getAttribute('aria-expanded') !== 'true';
    toggle.setAttribute('aria-expanded', String(open));
    toggle.setAttribute('aria-label', open ? 'Tutup menu' : 'Buka menu');
    navigation.classList.toggle('is-open', open);
});
document.addEventListener('keydown', event => {
    if (event.key === 'Escape' && toggle) {
        navigation.classList.remove('is-open');
        toggle.setAttribute('aria-expanded', 'false');
    }
});
const dialog = document.querySelector('#image-dialog');
document.querySelectorAll('[data-image]').forEach(button => button.addEventListener('click', () => {
    dialog.querySelector('img').src = button.dataset.image;
    dialog.querySelector('img').alt = button.dataset.caption;
    dialog.querySelector('p').textContent = button.dataset.caption;
    dialog.showModal();
}));
dialog?.querySelector('.dialog-close').addEventListener('click', () => dialog.close());
dialog?.addEventListener('click', event => { if (event.target === dialog) dialog.close(); });
document.querySelector('[data-visit-toggle]')?.addEventListener('click', event => {
    const form = document.querySelector('#visit-form');
    form.hidden = !form.hidden;
    event.currentTarget.setAttribute('aria-expanded', String(!form.hidden));
    if (!form.hidden) form.querySelector('input:not([type=hidden])')?.focus();
});
document.querySelectorAll('form[data-single-submit]').forEach(form => form.addEventListener('submit', () => {
    if (form.checkValidity()) form.querySelectorAll('button[type=submit]').forEach(button => {
        button.disabled = true;
        button.textContent = 'Mengirim…';
    });
}));
document.querySelectorAll('[data-confirm]').forEach(form => form.addEventListener('submit', event => {
    if (!window.confirm(form.dataset.confirm)) event.preventDefault();
}));
