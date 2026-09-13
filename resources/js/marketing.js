import Alpine from 'alpinejs';

window.Alpine = Alpine;

const prefersReducedMotion = () =>
    window.matchMedia('(prefers-reduced-motion: reduce)').matches;

function initScrollReveals() {
    const nodes = document.querySelectorAll('[data-mk-reveal]');

    if (!nodes.length) {
        return;
    }

    if (prefersReducedMotion() || !('IntersectionObserver' in window)) {
        nodes.forEach((node) => node.classList.add('is-visible'));
        return;
    }

    const observer = new IntersectionObserver(
        (entries, obs) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) {
                    return;
                }

                entry.target.classList.add('is-visible');
                obs.unobserve(entry.target);
            });
        },
        {
            threshold: 0.12,
            rootMargin: '0px 0px -10% 0px',
        },
    );

    nodes.forEach((node) => observer.observe(node));
}

function initNavScroll() {
    const nav = document.querySelector('[data-mk-nav]');

    if (!nav) {
        return;
    }

    const update = () => {
        nav.classList.toggle('is-scrolled', window.scrollY > 12);
    };

    update();
    window.addEventListener('scroll', update, { passive: true });
}

function animateCounter(el) {
    const target = Number(el.getAttribute('data-mk-target') || 0);
    const suffix = el.getAttribute('data-mk-suffix') || '';
    const prefix = el.getAttribute('data-mk-prefix') || '';
    const duration = Number(el.getAttribute('data-mk-duration') || 1200);

    if (!Number.isFinite(target) || target <= 0) {
        return;
    }

    if (prefersReducedMotion()) {
        el.textContent = `${prefix}${target}${suffix}`;
        return;
    }

    const start = performance.now();
    el.textContent = `${prefix}0${suffix}`;

    const tick = (now) => {
        const progress = Math.min((now - start) / duration, 1);
        const eased = 1 - Math.pow(1 - progress, 3);
        const current = Math.round(target * eased);
        el.textContent = `${prefix}${current}${suffix}`;

        if (progress < 1) {
            requestAnimationFrame(tick);
        } else {
            el.textContent = `${prefix}${target}${suffix}`;
        }
    };

    requestAnimationFrame(tick);
}

function initCounters() {
    const counters = document.querySelectorAll('[data-mk-counter]');

    if (!counters.length) {
        return;
    }

    if (!('IntersectionObserver' in window)) {
        counters.forEach((counter) => animateCounter(counter));
        return;
    }

    const observer = new IntersectionObserver(
        (entries, obs) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) {
                    return;
                }

                animateCounter(entry.target);
                obs.unobserve(entry.target);
            });
        },
        { threshold: 0.45 },
    );

    counters.forEach((counter) => observer.observe(counter));
}

function initScrollTop() {
    const button = document.querySelector('[data-mk-scroll-top]');

    if (!button) {
        return;
    }

    const update = () => {
        button.classList.toggle('is-active', window.scrollY > 100);
    };

    button.addEventListener('click', (event) => {
        event.preventDefault();
        window.scrollTo({
            top: 0,
            behavior: prefersReducedMotion() ? 'auto' : 'smooth',
        });
    });

    update();
    window.addEventListener('scroll', update, { passive: true });
}

document.addEventListener('alpine:init', () => {
    Alpine.data('marketingNav', () => ({
        open: false,
        toggle() {
            this.open = !this.open;
            document.body.classList.toggle('overflow-hidden', this.open);
        },
        close() {
            this.open = false;
            document.body.classList.remove('overflow-hidden');
        },
    }));

    Alpine.data('faqAccordion', (initial = null) => ({
        active: initial,
        toggle(id) {
            this.active = this.active === id ? null : id;
        },
        isOpen(id) {
            return this.active === id;
        },
    }));

    Alpine.data('pricingToggle', (initial = 'monthly') => ({
        billing: initial,
        setBilling(value) {
            this.billing = value;
        },
        isAnnual() {
            return this.billing === 'annual';
        },
    }));
});

document.addEventListener('DOMContentLoaded', () => {
    initScrollReveals();
    initNavScroll();
    initCounters();
    initScrollTop();
});

Alpine.start();
