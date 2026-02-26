window.loadContent = async (url, options = {}) => {

    // hacer que http://127.0.0.1:8000/group/1 sea /group/1
    url = url.replace(window.location.origin, '');
    console.log(`Cargando contenido desde: ${url}`);

    const {
        containerSelector = "#main-content",
        updateHistory = true,
        onSuccess = null
    } = options;

    try {
        const container = document.querySelector(containerSelector);
        if (!container) throw new Error(`No se encontró el contenedor: ${containerSelector}`);

        container.classList.add("loading");
        // show spinner
        const loader = document.getElementById('loader');
        if (loader) loader.style.display = 'block';

        const response = await fetch(url, {
            headers: {
                "X-Requested-With": "XMLHttpRequest"
            }
        });

        if (!response.ok) throw new Error("Error al cargar la vista");
        const html = await response.text();

        container.innerHTML = html;
        // initializeScripts();

        if (onSuccess) onSuccess();
        container.classList.remove("loading");
        if (loader) loader.style.display = 'none';

        if (updateHistory) {
            window.history.pushState({ url }, "", url);
        }
    } catch (error) {
        console.error(error);
        alert("No se pudo cargar la página");
        if (loader) loader.style.display = 'none';
    }
};

window.initializeScripts = (url) => {
    const path = url.split('/')[3];

    const scriptMap = {
        'home': 'initFormsTask',
        'goods': 'initFormsBien',
        'groups': 'initGroupFunctions',
        'records': 'initHistorialFunctions' // TODO: Crear función de inicialización para historial y agregar aquí
    };

    const scriptName = scriptMap[path];
    if (scriptName && typeof window[scriptName] === 'function') {
        window[scriptName]();
    }

    console.log("Scripts inicializados", scriptName);
};

document.addEventListener("DOMContentLoaded", () => {
    const links = document.querySelectorAll("a[data-nav]");

    links.forEach(link => {
        link.addEventListener("click", (e) => {
            e.preventDefault();
            const url = link.getAttribute("href");

            // Carga el contenido al hacer clic en un enlace
            loadContent(url, { onSuccess: () => initializeScripts(url) });

            // Actualiza la URL sin recargar
            window.history.pushState({ url }, "", url);
        });
    });

    // Soporte para botones "Atrás" y "Adelante"
    window.addEventListener("popstate", (e) => {
        if (e.state?.url) {
            // Carga el contenido al navegar con los botones
            loadContent(e.state.url);

            // Obtener la parte principal del path actual (por ejemplo, "goods" de "/goods/openmodal")
            const path = window.location.pathname.split('/')[1];

            // Quitar selección previa
            document.querySelectorAll('.sidebar a').forEach(a => a.classList.remove('selected'));

            // Marcar la opción actual
            const current = document.getElementById(path==="inventory" ? "inventories": path);
            current.classList.add('selected');
        }
    });

    // Inicializa los scripts al cargar la página
    // initializeScripts();
});
