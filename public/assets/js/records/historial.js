function iniciarBusquedaHistorial(searchInputID) {
    // Obtiene el campo de entrada para la búsqueda
    const searchInput = document.getElementById(searchInputID);
    if (!searchInput) {
        // Muestra una advertencia si no se encuentra el campo de búsqueda
        console.warn("No se encontró el campo de búsqueda.");
        return;
    }

    // Agrega un evento para detectar cuando el usuario escribe en el campo de búsqueda
    searchInput.addEventListener('keyup', function () {
        // Convierte el texto ingresado a minúsculas para una búsqueda insensible a mayúsculas
        const filter = searchInput.value.toLowerCase();
        // Obtiene todas las tarjetas de bienes
        const cards = document.querySelectorAll(".card-item");

        // Itera sobre cada tarjeta y verifica si coincide con el texto de búsqueda
        cards.forEach(item => {
            const text = item.querySelector(".name-item").textContent.toLowerCase();
            // Muestra u oculta la tarjeta según si coincide con el filtro
            item.style.display = text.includes(filter) ? '' : 'none';
        });
    });
}

function initHistorialFunctions() {
    inicializarHistorial();
}

function activarBusquedaEnTablaHistorial() {
    const searchInput = document.getElementById('searchRecordInput');
    searchInput.addEventListener('keyup', function () {
        const filter = searchInput.value.toLowerCase();
        const rows = document.querySelectorAll("table tbody tr");

        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(filter) ? '' : 'none';
        });
    });
}


// Función para inicializar todo
function inicializarHistorial() {
    iniciarBusquedaHistorial('searchRecordInput');
    activarBusquedaEnTablaHistorial();
    
    // Asegurarse de que la estructura del contenedor de búsqueda sea correcta
    const searchInput = document.getElementById('searchRecordInput');
    if (searchInput && !searchInput.parentElement.classList.contains('search-container')) {
        const wrapper = document.createElement('div');
        wrapper.className = 'search-container';
        wrapper.style.position = 'relative';
        searchInput.parentNode.insertBefore(wrapper, searchInput);
        wrapper.appendChild(searchInput);
    }

    // Cargar usuarios en la sección de filtros - SOLUCIONADO: Evitar duplicados
    const userListContainer = document.getElementById('userList');
    if (userListContainer && window.allUserNames) {
        // Limpiar contenedor primero para evitar duplicados
        userListContainer.innerHTML = '';
        
        // Usar Set para eliminar usuarios duplicados
        const uniqueUsers = [...new Set(window.allUserNames)];
        
        uniqueUsers.forEach(userName => {
            const userCheckbox = document.createElement('label');
            userCheckbox.className = 'checkbox-container';
            userCheckbox.innerHTML = `
                <input type="checkbox" class="user-checkbox" value="${userName}">
                <span class="checkmark"></span>
                <span class="checkbox-label">${userName}</span>
            `;
            userListContainer.appendChild(userCheckbox);
        });
        
        console.log(`✅ Usuarios cargados: ${uniqueUsers.length} únicos de ${window.allUserNames.length} totales`);
    }
}
