const contactRows = document.querySelectorAll('[data-contact-row]');

if (contactRows.length > 0) {
    const mobileQuery = window.matchMedia('(max-width: 760px)');
    const interactiveSelector = 'a, button, input, select, textarea, label';

    const syncRows = () => {
        contactRows.forEach((row) => {
            const href = row.dataset.contactUrl;
            if (!href) {
                return;
            }

            if (mobileQuery.matches) {
                row.classList.add('private-table__row--clickable');
                row.tabIndex = 0;
                row.setAttribute('role', 'link');
            } else {
                row.classList.remove('private-table__row--clickable');
                row.removeAttribute('tabindex');
                row.removeAttribute('role');
            }
        });
    };

    const navigateToContact = (row) => {
        const href = row.dataset.contactUrl;
        if (href) {
            window.location.assign(href);
        }
    };

    contactRows.forEach((row) => {
        row.addEventListener('click', (event) => {
            if (!mobileQuery.matches) {
                return;
            }

            const target = event.target;
            if (!target || typeof target.closest !== 'function') {
                return;
            }

            if (target.closest(interactiveSelector)) {
                return;
            }

            navigateToContact(row);
        });

        row.addEventListener('keydown', (event) => {
            if (!mobileQuery.matches) {
                return;
            }

            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                navigateToContact(row);
            }
        });
    });

    syncRows();
    mobileQuery.addEventListener('change', syncRows);
}
