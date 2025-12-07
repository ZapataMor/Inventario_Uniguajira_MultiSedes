// Inicializa el autocompletado para el campo de búsqueda de bienes
function initAutocompleteForBien() {
    // No necesitamos cambiar clases ya que usamos la original
    console.log("Inicializando autocomplete para bienes");
    const autocomplete = initAutocompleteSearch('#search-container', {
        dataUrl: '/api/goods/get/json',
        inputSelector: '#nombreBienEnInventario',
        listSelector: '.suggestions', // Usar la clase original
        hiddenInputSelector: '#bien_id',
        onSelect: function(item) {
            // Convertir a minúsculas para hacer la comparación insensible a mayúsculas/minúsculas
            item.tipo = item.tipo.toLowerCase();

            // Llamar a la función que gestiona los campos
            gestionarCamposBien(item);
        },
        noMatchText: 'No se encontraron bienes con ese nombre',
        noDataText: 'No hay bienes disponibles'
    });

    // Variable global para acceder a autocomplete desde cualquier parte
    window.globalAutocomplete = autocomplete; // Cambiar a window.globalAutocomplete
    console.log(globalAutocomplete)

    // Ocultar sugerencias al abrir o cerrar el modal
    const modalBtn = document.querySelector('[data-target="#modalCrearBienInventario"]');
    if (modalBtn) {
        modalBtn.addEventListener('click', () => autocomplete.ocultarSugerencias());
    }

    document.querySelector('#modalCrearBienInventario .close').addEventListener('click', () => {
        autocomplete.ocultarSugerencias();
        // Reiniciar formulario al cerrar el modal
        resetearFormulario();
    });

    // Añadir manejo para el caso en que se presione Enter con sugerencia única
    const nombreBienInput = document.getElementById('nombreBienEnInventario');
    nombreBienInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            const suggestions = document.querySelector('.suggestions');
            const items = suggestions.querySelectorAll('li:not(.text-danger)');

            // Si hay exactamente una sugerencia no de error
            if (items.length === 1) {
                e.preventDefault(); // Evitar envío del formulario
                // La selección se maneja en el autocomplete
            }
        }
    });

    // Añadir clases a los inputs para estilizarlos
    const nombreBienEnInventario = document.getElementById('nombreBienEnInventario');
    if (nombreBienEnInventario) {
        nombreBienEnInventario.classList.add('form-input');
    }

    // NUEVO: Precargar los datos del autocomplete al inicializar
    precargarDatosAutocomplete(nombreBienInput, autocomplete);
}

// NUEVA FUNCIÓN: Precargar datos para el autocomplete
function precargarDatosAutocomplete(inputElement, autocomplete) {
    // Usamos setTimeout para asegurarnos de que esto ocurra después de que todo se haya cargado
    setTimeout(() => {
        // Primero, simular que se escribe un carácter (esto activará la carga de datos)
        inputElement.value = ".";
        inputElement.dispatchEvent(new Event('input', { bubbles: true }));

        // Después de un breve momento, borrar el carácter y ocultar las sugerencias
        setTimeout(() => {
            inputElement.value = "";
            autocomplete.ocultarSugerencias();
        }, 100);
    }, 500);
}

// Gestiona la visibilidad de los campos dinámicos según el tipo de bien seleccionado
function gestionarCamposBien(item) {
    const dynamicFields = document.getElementById('dynamicFields');
    const camposCantidad = document.getElementById('camposCantidad');
    const camposSerial = document.getElementById('camposSerial');
    const cantidadInput = document.getElementById('cantidadBien');
    const tipo = document.getElementById('bien_tipo');

    // Mostrar la sección de campos dinámicos con animación suave
    dynamicFields.style.display = 'block';
    dynamicFields.style.opacity = '0';
    setTimeout(() => {
        dynamicFields.style.opacity = '1';
    }, 50);

    // Ocultar ambos tipos de campos inicialmente
    camposCantidad.style.display = 'none';
    camposCantidad.classList.remove('active');
    camposSerial.style.display = 'none';
    camposSerial.classList.remove('active');

    // Determinar qué campos mostrar según el tipo de bien
    if (item.tipo.toLowerCase() === 'cantidad') {
        // Mostrar y habilitar campo de cantidad
        camposCantidad.style.display = 'block';

        // Pequeño retraso para la animación
        setTimeout(() => {
            camposCantidad.classList.add('active');
            // Desplazar automáticamente hacia la sección visible
            camposCantidad.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }, 50);

        tipo.value = 1;  // tipo cantidad
        cantidadInput.disabled = false;
        cantidadInput.value = 1;
        cantidadInput.focus();
    } else if (item.tipo.toLowerCase() === 'serial') {
        // Mostrar campos de serial
        camposSerial.style.display = 'block';
        tipo.value = 2;  // tipo serial

        // Pequeño retraso para la animación
        setTimeout(() => {
            camposSerial.classList.add('active');
            // Desplazar automáticamente hacia la sección visible
            camposSerial.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }, 50);

        limpiarCamposSerial();

        // Habilitar todos los campos de serial
        const camposSerialInputs = camposSerial.querySelectorAll('input, textarea, select');
        camposSerialInputs.forEach(campo => {
            campo.disabled = false;
            campo.classList.add('form-input');
        });

        // Enfocar el primer campo (descripción)
        document.getElementById('descripcionBien').focus();
    }

    // Ocultar sugerencias explícitamente
    document.querySelector('.suggestions').style.display = 'none';
}

// Función modificada para limpiar y también habilitar los campos de serial
function limpiarCamposSerial() {
    const fieldsToClear = [
        'descripcionBien', 'marcaBien', 'modeloBien', 'serialBien',
        'estadoBien', 'colorBien', 'condicionBien', 'fechaIngresoBien'
    ];

    fieldsToClear.forEach(id => {
        const field = document.getElementById(id);
        if (field) {
            field.value = '';
            field.disabled = false;
            field.classList.add('form-input');
        }
    });

    // Establecer fecha actual para el campo de fecha
    const hoy = new Date();
    const año = hoy.getFullYear();
    const mes = String(hoy.getMonth() + 1).padStart(2, '0'); // getMonth() devuelve de 0 a 11
    const dia = String(hoy.getDate()).padStart(2, '0');
    const fechaActual = `${año}-${mes}-${dia}`;
    const fechaField = document.getElementById('fechaIngresoBien');
    fechaField.value = fechaActual;
    fechaField.disabled = false;
}

// Nueva función para resetear el formulario completamente
function resetearFormulario() {
    // Limpiar campo de búsqueda
    const nombreBienInput = document.getElementById('nombreBienEnInventario');
    if (nombreBienInput) {
        nombreBienInput.value = '';
    }

    // Ocultar secciones dinámicas
    const dynamicFields = document.getElementById('dynamicFields');
    if (dynamicFields) {
        dynamicFields.style.display = 'none';
    }

    // Limpiar y ocultar sección de cantidad
    const camposCantidad = document.getElementById('camposCantidad');
    if (camposCantidad) {
        camposCantidad.style.display = 'none';
        camposCantidad.classList.remove('active');
        const cantidadInput = document.getElementById('cantidadBien');
        if (cantidadInput) {
            cantidadInput.value = '1';
        }
    }

    // Limpiar y ocultar sección de serial
    const camposSerial = document.getElementById('camposSerial');
    if (camposSerial) {
        camposSerial.style.display = 'none';
        camposSerial.classList.remove('active');
        limpiarCamposSerial();
    }

    // Limpiar campo oculto de ID
    const bienIdInput = document.getElementById('bien_id');
    if (bienIdInput) {
        bienIdInput.value = '';
    }
}
