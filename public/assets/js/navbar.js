function syncNavbarHeightVar() {
    const navbar = document.querySelector('body > header');
    if (!navbar) {
        return;
    }

    const navbarHeight = Math.ceil(navbar.getBoundingClientRect().height);
    document.documentElement.style.setProperty('--app-navbar-height', `${navbarHeight}px`);
}

/*Animacion al clickear el boton de menu de usuario*/
function positionUserMenu() {
    const userMenu = document.getElementById('userMenu');
    const userTrigger = document.querySelector('[data-user-trigger]') || document.querySelector('.user');

    if (!userMenu || !userTrigger) {
        return;
    }

    const triggerRect = userTrigger.getBoundingClientRect();
    const menuWidth = userMenu.offsetWidth || 220;
    const menuHeight = userMenu.offsetHeight || 200;
    const gap = 10;
    const viewportPadding = 8;

    let left = triggerRect.right + gap;
    let top = triggerRect.top + (triggerRect.height / 2) - (menuHeight / 2);

    if (left + menuWidth > window.innerWidth - viewportPadding) {
        left = window.innerWidth - menuWidth - viewportPadding;
    }

    if (top < viewportPadding) {
        top = viewportPadding;
    }

    if (top + menuHeight > window.innerHeight - viewportPadding) {
        top = window.innerHeight - menuHeight - viewportPadding;
    }

    userMenu.style.left = `${Math.max(viewportPadding, left)}px`;
    userMenu.style.top = `${Math.max(viewportPadding, top)}px`;
}

function toggleUserMenu() {
    const menu = document.getElementById('userMenu');
    if (!menu) {
        return;
    }

    const willOpen = menu.classList.contains('hidden');
    if (!willOpen) {
        menu.classList.add('hidden');
        return;
    }

    menu.classList.remove('hidden');
    positionUserMenu();
}

function logout() {
    // Eliminar la ultima opcion seleccionada antes de cerrar sesion
    localStorage.removeItem('lastSelected');
    localStorage.removeItem('openGroup');
    localStorage.removeItem('openInventory');
}

document.addEventListener('DOMContentLoaded', syncNavbarHeightVar);
window.addEventListener('load', syncNavbarHeightVar);
window.addEventListener('resize', syncNavbarHeightVar);

// Cerrar el menu al hacer clic fuera de el
document.addEventListener('click', function (event) {
    const userMenu = document.getElementById('userMenu');
    const userTrigger = document.querySelector('[data-user-trigger]') || document.querySelector('.user');
    const clickedOnTrigger = userTrigger ? userTrigger.contains(event.target) : false;

    // Si el menu esta visible y el clic no fue dentro del menu ni sobre el trigger de usuario
    if (userMenu && !userMenu.classList.contains('hidden') &&
        !userMenu.contains(event.target) && !clickedOnTrigger) {
        userMenu.classList.add('hidden');
    }
});

window.addEventListener('resize', () => {
    const userMenu = document.getElementById('userMenu');
    if (userMenu && !userMenu.classList.contains('hidden')) {
        positionUserMenu();
    }
});
