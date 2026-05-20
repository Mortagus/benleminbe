const readablePage = document.querySelector(
    '.experience-detail-page, .project-detail-page',
);

if (readablePage) {
    const progress = document.createElement('div');

    progress.className = 'reading-progress';
    progress.setAttribute('aria-hidden', 'true');
    document.body.append(progress);

    let ticking = false;

    function updateProgress() {
        const scrollableHeight =
            document.documentElement.scrollHeight - window.innerHeight;
        const ratio = scrollableHeight > 0
            ? Math.min(window.scrollY / scrollableHeight, 1)
            : 0;

        progress.style.setProperty('--reading-progress', `${ratio}`);
        progress.classList.toggle('is-hidden', scrollableHeight <= 0);
        ticking = false;
    }

    function requestProgressUpdate() {
        if (!ticking) {
            window.requestAnimationFrame(updateProgress);
            ticking = true;
        }
    }

    updateProgress();
    window.addEventListener('scroll', requestProgressUpdate, { passive: true });
    window.addEventListener('resize', requestProgressUpdate);
}
