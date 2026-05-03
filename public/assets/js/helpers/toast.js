function showToast(msg) {
    const toastId = `toast-${Date.now()}`;
    const toastType = msg.success ? "toast-success" : (msg.info ? "toast-info" : "toast-fail");
    const icon = msg.success ? '✅' : (msg.info ? 'ℹ️' : '❌');
    const toastTitle = msg.success ? 'Éxito' : (msg.info ? 'Sugerencia' : 'Error');
    const toastMessage = msg.message || (msg.success ? 'Operación completada' : 'Algo salió mal');

    // Estructura del toast
    const toastHTML = `
        <div id="${toastId}" class="toast ${toastType} align-items-center" role="alert">
            <div class="d-flex">
                <div class="toast-body">
                    <!--<span>${icon}</span>--><strong>${toastTitle}</strong>: ${toastMessage}
                </div>
                <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    `;

    const container = document.getElementById('toastContainer');
    container.insertAdjacentHTML('beforeend', toastHTML);
    
    const toastElement = document.getElementById(toastId);
    const toast = new bootstrap.Toast(toastElement, {
        delay: msg.info ? 4000 : 5000
    });
    toast.show();
    
    // Auto-remove cuando se oculta
    toastElement.addEventListener('hidden.bs.toast', () => {
        toastElement.remove();
    });
}

// Ejemplos de uso
// document.getElementById('successBtn').addEventListener('click', () => {
//     showToast({
//         success: true,
//         message: 'Los datos se guardaron correctamente'
//     });
// });

// document.getElementById('errorBtn').addEventListener('click', () => {
//     showToast({
//         success: false,
//         message: 'No se pudo conectar al servidor'
//     });
// });

// ------------ TODO: Probar este alternativa a showToast
// /**
//  * Función auxiliar para mostrar notificaciones
//  * @param {Object} response - Respuesta del servidor
//  */
// function showToast(response) {
//     // Esta función asume que existe alguna librería de notificaciones
//     // Si estás usando SweetAlert2:
//     if (typeof Swal !== 'undefined') {
//         Swal.fire({
//             icon: response.success !== false ? 'success' : 'error',
//             title: response.success !== false ? 'Éxito' : 'Error',
//             text: response.message || (response.success !== false ? 'Operación completada' : 'Ha ocurrido un error'),
//             timer: 3000,
//             timerProgressBar: true
//         });
//     } else {
//         // Alternativa básica si no hay librería de notificaciones
//         alert(response.message || (response.success !== false ? 'Operación completada' : 'Ha ocurrido un error'));
//     }
// }