document.addEventListener("DOMContentLoaded", () => {
    const main = document.getElementById("main-content");
    const links = document.querySelectorAll("a[data-nav]");

    links.forEach(link => {
        link.addEventListener("click", async (e) => {
            e.preventDefault();
            const url = link.getAttribute("href");

            try {
                // Agrega una clase de carga
                main.classList.add("loading");

                // Realiza la solicitud AJAX
                const response = await fetch(url, {
                    headers: {
                        "X-Requested-With": "XMLHttpRequest"
                    }
                });

                if (!response.ok) throw new Error("Error al cargar la vista");
                const html = await response.text();

                // Reemplaza el contenido del main
                main.innerHTML = html;

                // Actualiza la URL sin recargar
                window.history.pushState({ url }, "", url);

                // Quita la clase de carga
                main.classList.remove("loading");
            } catch (error) {
                console.error(error);
                alert("No se pudo cargar la página");
            }
        });
    });

    // Soporte para botones "Atrás" y "Adelante"
    window.addEventListener("popstate", async (e) => {
        if (e.state?.url) {
            const response = await fetch(e.state.url, {
                headers: { "X-Requested-With": "XMLHttpRequest" }
            });
            const html = await response.text();
            main.innerHTML = html;
        }
    });
});
