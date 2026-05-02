// assets/scripts/tracking.js

document.querySelectorAll('[data-track="cv"]').forEach((link) => {
    link.addEventListener('click', () => {
        navigator.sendBeacon('/track/cv-download');
    });
});
