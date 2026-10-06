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
    const image = dialog.querySelector('img');
    image.src = button.dataset.image;
    image.alt = button.dataset.caption;
    dialog.querySelector('p').textContent = button.dataset.caption;
    const crop = button.dataset.facilityCrop?.split(' ').map(Number);
    dialog.classList.toggle('facility-crop', Boolean(crop));
    if (crop) {
        dialog.style.setProperty('--crop-x', `${-crop[0] * 100}%`);
        dialog.style.setProperty('--crop-y', `${-crop[1] * 100}%`);
    } else {
        dialog.style.removeProperty('--crop-x');
        dialog.style.removeProperty('--crop-y');
    }
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

const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
const heroSlider = document.querySelector('[data-hero-slider]');
if (heroSlider) {
    const slides = [...heroSlider.querySelectorAll('.hero-slide')];
    let activeIndex = Math.max(0, slides.findIndex(slide => slide.classList.contains('is-active')));
    let pausedByUser = reduceMotion;
    let timer;
    const playbackButton = heroSlider.querySelector('[data-hero-toggle]');

    const syncPlaybackButton = () => {
        if (!playbackButton) return;
        playbackButton.hidden = false;
        playbackButton.textContent = pausedByUser ? 'Lanjutkan' : 'Jeda';
        playbackButton.setAttribute('aria-label', `${pausedByUser ? 'Lanjutkan' : 'Jeda'} tayangan gambar`);
    };

    const showSlide = index => {
        activeIndex = (index + slides.length) % slides.length;
        slides.forEach((slide, slideIndex) => {
            const active = slideIndex === activeIndex;
            slide.classList.toggle('is-active', active);
            slide.setAttribute('aria-hidden', String(!active));
        });
        const caption = heroSlider.querySelector('[data-hero-caption]');
        if (caption) caption.textContent = slides[activeIndex].dataset.caption;
        const conceptNote = heroSlider.querySelector('[data-hero-note]');
        if (conceptNote) conceptNote.hidden = !slides[activeIndex].dataset.concept;
    };
    const conceptNote = heroSlider.querySelector('[data-hero-note]');
    if (conceptNote) conceptNote.hidden = !slides[activeIndex].dataset.concept;
    const stopAutoplay = () => {
        window.clearInterval(timer);
        timer = undefined;
    };
    const startAutoplay = () => {
        stopAutoplay();
        if (pausedByUser || document.hidden || slides.length < 2
            || heroSlider.matches(':hover') || heroSlider.contains(document.activeElement)) return;
        timer = window.setInterval(() => showSlide(activeIndex + 1), 5500);
    };

    syncPlaybackButton();
    playbackButton?.addEventListener('click', () => {
        pausedByUser = !pausedByUser;
        syncPlaybackButton();
        if (pausedByUser) stopAutoplay();
        else startAutoplay();
    });
    heroSlider.addEventListener('pointerenter', stopAutoplay);
    heroSlider.addEventListener('pointerleave', startAutoplay);
    heroSlider.addEventListener('focusin', stopAutoplay);
    heroSlider.addEventListener('focusout', event => {
        if (!heroSlider.contains(event.relatedTarget)) startAutoplay();
    });
    document.addEventListener('visibilitychange', startAutoplay);
    startAutoplay();
}

if (!reduceMotion && 'IntersectionObserver' in window) {
    const revealSelector = [
        '.hero-copy', '.hero-image', '.section-heading', '.program-card',
        '.advantage-card', '.info-card', '.team-card', '.testimonial',
        '.principal-card', '.admission-steps > article', '.school-stats', '.cta',
    ].join(',');
    const revealItems = [...document.querySelectorAll(revealSelector)];
    const staggerSelector = '.program-card,.advantage-card,.info-card,.team-card,.testimonial,.admission-steps > article';
    const siblingIndexes = new Map();

    revealItems.forEach(item => {
        item.classList.add('motion-reveal');
        if (!item.matches(staggerSelector)) return;

        const index = siblingIndexes.get(item.parentElement) || 0;
        item.style.setProperty('--motion-delay', `${Math.min(index, 4) * 70}ms`);
        siblingIndexes.set(item.parentElement, index + 1);
    });

    const observer = new IntersectionObserver(entries => {
        entries.forEach(entry => {
            if (!entry.isIntersecting) return;
            entry.target.classList.add('is-visible');
            observer.unobserve(entry.target);
        });
    }, { threshold: 0.12, rootMargin: '0px 0px -32px 0px' });

    document.body.classList.add('motion-ready');
    revealItems.forEach(item => observer.observe(item));
}

const whatsappFloat = document.querySelector('[data-whatsapp-float]');
if (whatsappFloat && window.matchMedia('(hover: hover) and (pointer: fine)').matches) {
    let hideTimer;
    const activationZone = 150;
    const showWhatsApp = () => {
        window.clearTimeout(hideTimer);
        whatsappFloat.classList.add('is-visible');
    };
    const scheduleWhatsAppHide = () => {
        window.clearTimeout(hideTimer);
        hideTimer = window.setTimeout(() => {
            if (!whatsappFloat.matches(':hover') && !whatsappFloat.contains(document.activeElement)) {
                whatsappFloat.classList.remove('is-visible');
            }
        }, 700);
    };

    window.addEventListener('pointermove', event => {
        if (event.clientX >= window.innerWidth - activationZone
            && event.clientY >= window.innerHeight - activationZone) {
            showWhatsApp();
        } else {
            scheduleWhatsAppHide();
        }
    }, { passive: true });
    whatsappFloat.addEventListener('pointerenter', showWhatsApp);
    whatsappFloat.addEventListener('pointerleave', scheduleWhatsAppHide);
    whatsappFloat.addEventListener('focus', showWhatsApp);
    whatsappFloat.addEventListener('blur', scheduleWhatsAppHide);
}
