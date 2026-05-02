const STORAGE_KEY = 'preferred-theme';
const root = document.documentElement;
const buttons = document.querySelectorAll('[data-theme-choice]');

function applyTheme(theme) {
    if (theme === 'system') {
        root.removeAttribute('data-theme');
    } else {
        root.setAttribute('data-theme', theme);
    }

    buttons.forEach((button) => {
        button.setAttribute(
            'aria-pressed',
            button.dataset.themeChoice === theme ? 'true' : 'false'
        );
    });
}

const savedTheme = localStorage.getItem(STORAGE_KEY) || 'system';

applyTheme(savedTheme);

buttons.forEach((button) => {
    button.addEventListener('click', () => {
        const theme = button.dataset.themeChoice;

        localStorage.setItem(STORAGE_KEY, theme);
        applyTheme(theme);
    });
});
