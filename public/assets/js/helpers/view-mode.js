(() => {
    const storagePrefix = 'inventario:view-mode:';
    const defaultListClasses = ['grid-cols-1'];

    const parseClasses = (value) => String(value || '')
        .split(/\s+/)
        .map((className) => className.trim())
        .filter(Boolean);

    const getStoredMode = (key) => {
        try {
            return localStorage.getItem(`${storagePrefix}${key}`) === 'list' ? 'list' : 'grid';
        } catch (error) {
            return 'grid';
        }
    };

    const storeMode = (key, mode) => {
        try {
            localStorage.setItem(`${storagePrefix}${key}`, mode);
        } catch (error) {
            // Local storage may be unavailable in private or restricted contexts.
        }
    };

    const getRootKey = (root) => root?.dataset.viewModeRoot || 'default';

    const findRoot = (button) => {
        const target = button?.dataset.viewModeToggle;
        return button?.closest('[data-view-mode-root]')
            || document.querySelector(`[data-view-mode-root="${target}"]`);
    };

    const getContainers = (root) => root
        ? root.querySelectorAll('[data-view-mode-container], .card-grid, .bienes-grid')
        : [];

    const setContainerMode = (container, mode) => {
        const isList = mode === 'list';
        const listClasses = parseClasses(container.dataset.viewListClasses);
        const classes = listClasses.length ? listClasses : defaultListClasses;

        classes.forEach((className) => {
            container.classList.toggle(className, isList);
        });

        if (isList) {
            container.style.setProperty('grid-template-columns', '1fr', 'important');
            return;
        }

        container.style.removeProperty('grid-template-columns');
    };

    const setButtonState = (button, mode) => {
        const isList = mode === 'list';
        const gridIcon = button.querySelector('[data-view-mode-icon="grid"]');
        const listIcon = button.querySelector('[data-view-mode-icon="list"]');
        const label = button.querySelector('[data-view-mode-label]');

        gridIcon?.classList.toggle('hidden', isList);
        listIcon?.classList.toggle('hidden', !isList);
        button.classList.toggle('border-emerald-500', isList);
        button.classList.toggle('bg-emerald-50', isList);
        button.classList.toggle('text-emerald-700', isList);
        button.setAttribute('aria-pressed', isList ? 'true' : 'false');
        button.setAttribute(
            'title',
            isList
                ? 'Vista actual: lista. Cambiar a tarjetas'
                : 'Vista actual: tarjetas. Cambiar a lista'
        );

        if (label) {
            label.textContent = isList ? 'Vista en lista' : 'Vista en tarjetas';
        }
    };

    const applyMode = (root, mode) => {
        if (!root) {
            return;
        }

        root.dataset.viewMode = mode;

        getContainers(root).forEach((container) => {
            setContainerMode(container, mode);
        });

        root.querySelectorAll('[data-view-mode-button]').forEach((button) => {
            setButtonState(button, mode);
        });
    };

    const initRoot = (root) => {
        const key = getRootKey(root);
        applyMode(root, getStoredMode(key));
    };

    window.initViewModeToggles = () => {
        document.querySelectorAll('[data-view-mode-root]').forEach(initRoot);
    };

    const initSoon = () => {
        window.requestAnimationFrame(() => window.initViewModeToggles());
    };

    const wrapLoadContent = () => {
        if (typeof window.loadContent !== 'function' || window.loadContent.__viewModeWrapped) {
            return;
        }

        const originalLoadContent = window.loadContent;

        window.loadContent = async (...args) => {
            const response = await originalLoadContent(...args);
            initSoon();
            return response;
        };

        window.loadContent.__viewModeWrapped = true;
        window.loadContent.__viewModeOriginal = originalLoadContent;
    };

    document.addEventListener('click', (event) => {
        const button = event.target.closest('[data-view-mode-button]');

        if (!button) {
            return;
        }

        const root = findRoot(button);
        const key = getRootKey(root);
        const currentMode = root?.dataset.viewMode === 'list' ? 'list' : getStoredMode(key);
        const nextMode = currentMode === 'list' ? 'grid' : 'list';

        event.preventDefault();
        applyMode(root, nextMode);
        storeMode(key, nextMode);
    });

    document.addEventListener('DOMContentLoaded', () => {
        initSoon();
        wrapLoadContent();
        window.setTimeout(wrapLoadContent, 0);
    });
})();
