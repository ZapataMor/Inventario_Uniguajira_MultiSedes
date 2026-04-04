/*Animacion al clickear el boton de menu de usuario*/
function toggleUserMenu() {
    const menu = document.getElementById('userMenu');
    menu.classList.toggle('hidden'); // Usar una sola clase para mostrar/ocultar
}

function logout() {
    // Eliminar la ultima opcion seleccionada antes de cerrar sesion
    localStorage.removeItem('lastSelected');
    localStorage.removeItem('openGroup');
    localStorage.removeItem('openInventory');
}

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
