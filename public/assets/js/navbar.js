/*Animacion al cliquear el boton de Menu de usuario*/
function toggleUserMenu() {
    const menu = document.getElementById('userMenu');
    menu.classList.toggle('hidden'); // Usar una sola clase para mostrar/ocultar
}

function logout() {
    // Eliminar la última opción seleccionada antes de cerrar sesión
    localStorage.removeItem('lastSelected');
    localStorage.removeItem('openGroup');
    localStorage.removeItem('openInventory');
}

// Cerrar el menú al hacer clic fuera de él
document.addEventListener('click', function (event) {
    const userMenu = document.getElementById('userMenu');
    const userImage = document.querySelector('.user');

    // Si el menú está visible y el clic no fue dentro del menú ni sobre la imagen del usuario
    if (userMenu && !userMenu.classList.contains('hidden') &&
        !userMenu.contains(event.target) && !userImage.contains(event.target)) {
        userMenu.classList.add('hidden');
    }
});
