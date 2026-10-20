const toggle = document.querySelector('.menu-toggle');
document.querySelectorAll('[data-hero-motion]').forEach(button => {
    button.addEventListener('click', () => {
        const paused = button.closest('.hero').classList.toggle('is-motion-paused');
        button.setAttribute('aria-pressed', String(paused));
        button.setAttribute('aria-label', paused ? 'Putar animasi dekorasi' : 'Jeda animasi dekorasi');
        button.innerHTML = paused ? '▷ <span>Putar animasi</span>' : 'Ⅱ <span>Jeda animasi</span>';
    });
});
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
document.querySelectorAll('[data-upload]').forEach(dropzone => {
    const input = dropzone.querySelector('input[type=file]');
    const fileName = dropzone.querySelector('[data-upload-name]');
    const feedback = dropzone.parentElement?.querySelector('[data-upload-error]');
    const maxSize = Number(dropzone.dataset.maxSize || 0);
    const allowedExtensions = (input?.accept || '').split(',').map(value => value.trim().toLowerCase()).filter(value => value.startsWith('.'));
    const preview = dropzone.querySelector('[data-upload-preview]');
    const previewImage = dropzone.querySelector('[data-upload-preview-image]');
    const previewDocument = dropzone.querySelector('[data-upload-preview-document]');
    const previewStatus = dropzone.querySelector('[data-upload-preview-status]');
    const previewMeta = dropzone.querySelector('[data-upload-preview-meta]');
    let previewUrl;

    const formatFileSize = bytes => {
        if (bytes < 1024) return `${bytes} B`;
        if (bytes >= 1024 * 1024) return `${(bytes / (1024 * 1024)).toLocaleString('id-ID', { maximumFractionDigits: 1 })} MB`;
        return `${(bytes / 1024).toLocaleString('id-ID', { maximumFractionDigits: 1 })} KB`;
    };
    const clearPreviewUrl = () => {
        if (previewUrl) URL.revokeObjectURL(previewUrl);
        previewUrl = undefined;
    };
    const renderFile = file => {
        clearPreviewUrl();
        dropzone.classList.toggle('is-ready', Boolean(file));
        if (fileName) fileName.textContent = file?.name ?? 'Belum memilih berkas';
        if (!preview) return;

        preview.hidden = !file;
        if (!file) {
            if (previewImage) {
                previewImage.hidden = true;
                previewImage.removeAttribute('src');
            }
            return;
        }

        const isImage = file.type.startsWith('image/') || /\.(jpe?g|png)$/i.test(file.name);
        if (previewImage) {
            previewImage.hidden = !isImage;
            if (isImage) {
                previewUrl = URL.createObjectURL(file);
                previewImage.src = previewUrl;
            } else {
                previewImage.removeAttribute('src');
            }
        }
        if (previewDocument) previewDocument.hidden = isImage;
        if (previewStatus) previewStatus.textContent = 'Siap diunggah';
        if (previewMeta) previewMeta.textContent = `${file.name} • ${formatFileSize(file.size)}`;
    };

    input?.addEventListener('change', () => {
        const file = input.files?.[0];
        let message = '';
        if (file && allowedExtensions.length && !allowedExtensions.some(extension => file.name.toLowerCase().endsWith(extension))) {
            message = `Format berkas belum sesuai. Pilih ${dropzone.dataset.formats || 'format yang tertera'}.`;
        } else if (file && maxSize && file.size > maxSize) {
            message = `Ukuran ${formatFileSize(file.size)} melebihi batas ${formatFileSize(maxSize)}. Pilih berkas yang lebih kecil.`;
        }
        input.setCustomValidity(message);
        input.setAttribute('aria-invalid', message ? 'true' : 'false');
        dropzone.classList.remove('has-error');
        dropzone.closest('.registration-upload-card')?.classList.remove('has-error');
        dropzone.classList.toggle('is-invalid', Boolean(message));
        dropzone.closest('.registration-upload-card')?.classList.toggle('is-invalid', Boolean(message));
        if (feedback) {
            feedback.textContent = message;
            feedback.hidden = !message;
        }
        renderFile(message ? null : file);
        if (message) {
            dropzone.classList.remove('is-ready');
            if (previewStatus) previewStatus.textContent = 'Perlu diganti';
        }
    });
    dropzone.addEventListener('dragover', event => {
        event.preventDefault();
        dropzone.classList.add('is-dragging');
    });
    dropzone.addEventListener('dragleave', () => dropzone.classList.remove('is-dragging'));
    dropzone.addEventListener('drop', event => {
        event.preventDefault();
        dropzone.classList.remove('is-dragging');
        if (!input || !event.dataTransfer?.files?.length) return;
        input.files = event.dataTransfer.files;
        input.dispatchEvent(new Event('change', { bubbles: true }));
    });
    window.addEventListener('beforeunload', clearPreviewUrl, { once: true });
});
document.querySelectorAll('.registration-form').forEach(form => form.addEventListener('submit', () => {
    if (!form.checkValidity()) return;
    form.querySelectorAll('[data-upload-preview-status]').forEach(status => {
        if (!status.closest('[data-upload-preview]')?.hidden) status.textContent = 'Mengunggah…';
    });
}));

document.querySelectorAll('[data-province-select]').forEach(province => {
    const regency = province.form?.querySelector('[data-regency-select]');
    if (!regency) return;
    const placeholder = regency.querySelector('option[value=""]');
    const groups = Array.from(regency.querySelectorAll('optgroup'));
    const updateRegencies = () => {
        const previous = regency.value;
        const selectedGroup = groups.find(group => group.dataset.province === province.value);
        regency.replaceChildren(placeholder, ...(selectedGroup ? [selectedGroup] : []));
        regency.value = Array.from(regency.options).some(option => option.value === previous) ? previous : '';
        regency.disabled = !province.value;
        placeholder.textContent = province.value ? 'Pilih kabupaten/kota' : 'Pilih provinsi terlebih dahulu';
    };
    province.addEventListener('change', updateRegencies);
    updateRegencies();
});
document.querySelectorAll('[data-confirm]').forEach(form => form.addEventListener('submit', event => {
    if (!window.confirm(form.dataset.confirm)) event.preventDefault();
}));

const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
const liquidPointer = window.matchMedia('(hover: hover) and (pointer: fine)');
document.querySelectorAll('[data-liquid-button]').forEach(button => {
    let liquidFrame;

    const moveLiquidGlow = event => {
        if (reduceMotion || !liquidPointer.matches) return;
        const bounds = button.getBoundingClientRect();
        const x = event.clientX - bounds.left;
        const y = event.clientY - bounds.top;

        window.cancelAnimationFrame(liquidFrame);
        liquidFrame = window.requestAnimationFrame(() => {
            button.style.setProperty('--liquid-x', `${x}px`);
            button.style.setProperty('--liquid-y', `${y}px`);
            button.classList.add('is-liquid-active');
        });
    };

    const resetLiquidGlow = () => {
        window.cancelAnimationFrame(liquidFrame);
        button.classList.remove('is-liquid-active');
        button.style.removeProperty('--liquid-x');
        button.style.removeProperty('--liquid-y');
    };

    button.addEventListener('pointermove', moveLiquidGlow, { passive: true });
    button.addEventListener('pointerleave', resetLiquidGlow);
    button.addEventListener('pointercancel', resetLiquidGlow);
    button.addEventListener('blur', resetLiquidGlow);
});
const ambientPointer = window.matchMedia('(hover: hover) and (pointer: fine)');
const ambientMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
document.querySelectorAll('.team-card').forEach(card => {
    let frame;
    let pointerX = 0;
    let pointerY = 0;

    const updateLight = event => {
        if (!ambientPointer.matches || event.pointerType === 'touch') return;
        card.classList.add('has-ambient-light');
        if (ambientMotion.matches) return;
        pointerX = event.clientX;
        pointerY = event.clientY;
        if (frame) return;
        frame = window.requestAnimationFrame(() => {
            const bounds = card.getBoundingClientRect();
            card.style.setProperty('--ambient-x', `${pointerX - bounds.left}px`);
            card.style.setProperty('--ambient-y', `${pointerY - bounds.top}px`);
            frame = undefined;
        });
    };
    const hideLight = () => {
        window.cancelAnimationFrame(frame);
        frame = undefined;
        card.classList.remove('has-ambient-light');
        card.style.removeProperty('--ambient-x');
        card.style.removeProperty('--ambient-y');
    };

    card.addEventListener('pointerenter', updateLight, { passive: true });
    card.addEventListener('pointermove', updateLight, { passive: true });
    card.addEventListener('pointerleave', hideLight);
    card.addEventListener('pointercancel', hideLight);
    ambientPointer.addEventListener('change', hideLight);
    ambientMotion.addEventListener('change', hideLight);
});
const heroSlider = document.querySelector('[data-hero-slider]');
if (heroSlider) {
    const slides = [...heroSlider.querySelectorAll('.hero-slide')];
    let activeIndex = Math.max(0, slides.findIndex(slide => slide.classList.contains('is-active')));
    let timer;
    let switching = false;

    const showSlide = async index => {
        if (switching) return;
        switching = true;
        const next = slides[(index + slides.length) % slides.length];
        if (!next.src && next.dataset.src) next.src = next.dataset.src;
        try {
            await next.decode();
        } catch {
            switching = false;
            return;
        }
        if (document.hidden) {
            switching = false;
            return;
        }
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
        switching = false;
    };
    const conceptNote = heroSlider.querySelector('[data-hero-note]');
    if (conceptNote) conceptNote.hidden = !slides[activeIndex].dataset.concept;
    const stopAutoplay = () => {
        window.clearInterval(timer);
        timer = undefined;
    };
    const startAutoplay = () => {
        stopAutoplay();
        if (reduceMotion || document.hidden || slides.length < 2) return;
        timer = window.setInterval(() => showSlide(activeIndex + 1), 5500);
    };

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
