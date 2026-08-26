const menuButton = document.querySelector('[data-menu-button]');
const header = document.querySelector('[data-header]');

menuButton?.addEventListener('click', () => {
    const isOpen = header?.classList.toggle('menu-open') ?? false;
    menuButton.setAttribute('aria-expanded', String(isOpen));
});

document.querySelectorAll('.site-nav a').forEach((link) => {
    link.addEventListener('click', () => {
        header?.classList.remove('menu-open');
        menuButton?.setAttribute('aria-expanded', 'false');
    });
});

const sidebar = document.querySelector('[data-sidebar]');
document.querySelector('[data-admin-menu]')?.addEventListener('click', () => {
    sidebar?.classList.toggle('open');
});

const reveals = document.querySelectorAll('.reveal');

if ('IntersectionObserver' in window && ! window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.12 });

    reveals.forEach((element) => observer.observe(element));
} else {
    reveals.forEach((element) => element.classList.add('visible'));
}
