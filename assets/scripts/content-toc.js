const toc = document.querySelector('[data-content-toc]');
const toggle = document.querySelector('[data-content-toc-toggle]');

function setTocState(isOpen) {
    toc?.classList.toggle('is-open', isOpen);
    toggle?.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
}

toggle?.addEventListener('click', () => {
    setTocState(toggle.getAttribute('aria-expanded') !== 'true');
});

toc?.addEventListener('click', (event) => {
    if (event.target instanceof HTMLAnchorElement) {
        setTocState(false);
    }
});

document.addEventListener('click', (event) => {
    if (
        toc
        && toggle
        && event.target instanceof Node
        && !toc.contains(event.target)
        && !toggle.contains(event.target)
        && toggle.getAttribute('aria-expanded') === 'true'
    ) {
        setTocState(false);
    }
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        setTocState(false);
    }
});
