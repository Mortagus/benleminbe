const header = document.querySelector('[data-site-header]');
const toggle = document.querySelector('[data-menu-toggle]');
const nav = document.querySelector('[data-site-nav]');

function setMenuState(isOpen) {
    header?.classList.toggle('is-menu-open', isOpen);
    toggle?.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    toggle?.setAttribute(
        'aria-label',
        isOpen ? toggle.dataset.labelClose : toggle.dataset.labelOpen,
    );
}

toggle?.addEventListener('click', () => {
    setMenuState(toggle.getAttribute('aria-expanded') !== 'true');
});

nav?.addEventListener('click', (event) => {
    if (event.target instanceof HTMLAnchorElement) {
        setMenuState(false);
    }
});

document.addEventListener('click', (event) => {
    if (
        header
        && event.target instanceof Node
        && !header.contains(event.target)
        && toggle?.getAttribute('aria-expanded') === 'true'
    ) {
        setMenuState(false);
    }
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        setMenuState(false);
    }
});
