// TODO: Punto de inicio

// Este script maneja la visibilidad del sidebar al hacer clic en el menú.
// Añade o quita la clase CSS 'menu-toggle' para mostrar u ocultar el sidebar.
const menu = document.getElementById('menu');
const sidebar = document.getElementById('sidebar');
const main = document.getElementById('main');

// Función para verificar si es móvil
const isMobile = () => window.matchMedia('(max-width: 500px)').matches;

// Función para alternar el menú
const toggleMenu = () => {
    sidebar.classList.toggle('menu-toggle');
    menu.classList.toggle('menu-toggle');
    main.classList.toggle('menu-toggle');
};

// Evento del menú (toggle)
menu.addEventListener('click', (e) => {
    e.stopPropagation(); // Evita que el evento se propague al documento
    toggleMenu();
});

// Cerrar menú al hacer clic fuera (solo en móviles)
document.addEventListener('click', (e) => {
    if (isMobile() && sidebar.classList.contains('menu-toggle')) {
        // Verifica si el clic fue fuera del sidebar y del botón del menú
        if (!sidebar.contains(e.target) && e.target !== menu && !menu.contains(e.target)) {
            toggleMenu();
        }
    }
});

// Cerrar sidebar al hacer clic en opciones (solo móvil)
document.querySelectorAll('#sidebar a').forEach(link => {
    link.addEventListener('click', () => {
        if (isMobile() && sidebar.classList.contains('menu-toggle')) {
            toggleMenu();
        }
    });
});

// Opcional: Prevenir el cierre al hacer clic dentro del sidebar
sidebar.addEventListener('click', (e) => {
    e.stopPropagation();
});


// asignar evento click a las etiquetas <a> del sidebar
// para asignar la clase selected al elemento clickeado
// y eliminar la clase selected de los demás elementos
const links = document.querySelectorAll('.sidebar a');
links.forEach(link => {
    link.addEventListener('click', () => {
        links.forEach(l => l.classList.remove('selected'));
        link.classList.add('selected');
    });
});

// Obtener la parte principal del path actual (por ejemplo, "goods" de "/goods/openmodal")
const path = window.location.pathname.split('/')[1];

// Quitar selección previa
document.querySelectorAll('.sidebar a').forEach(a => a.classList.remove('selected'));

// Marcar la opción actual
const current = document.getElementById(path==="groups" || path==="group" ? "inventories": path);
current.classList.add('selected');
