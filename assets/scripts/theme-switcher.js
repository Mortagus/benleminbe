const STORAGE_KEY = 'preferred-theme';
const root = document.documentElement;
const switcher = document.querySelector('[data-theme-switcher]');
const toggle = document.querySelector('[data-theme-toggle]');
const currentIcon = document.querySelector('[data-theme-icon]');
const buttons = document.querySelectorAll('[data-theme-choice]');

function applyTheme(theme) {
    if (theme === 'system') {
        root.removeAttribute('data-theme');
    } else {
        root.setAttribute('data-theme', theme);
    }

    buttons.forEach((button) => {
        const isActive = button.dataset.themeChoice === theme;

        button.setAttribute('aria-pressed', isActive ? 'true' : 'false');

        if (isActive && currentIcon) {
            currentIcon.textContent = button.dataset.themeIconValue;
        }
    });
}

const savedTheme = localStorage.getItem(STORAGE_KEY) || 'system';

applyTheme(savedTheme);

buttons.forEach((button) => {
    button.addEventListener('click', () => {
        const theme = button.dataset.themeChoice;

        localStorage.setItem(STORAGE_KEY, theme);
        applyTheme(theme);

        switcher?.classList.remove('is-open');
        toggle?.setAttribute('aria-expanded', 'false');
    });
});

toggle?.addEventListener('click', () => {
    const isOpen = switcher?.classList.toggle('is-open');

    toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
});
