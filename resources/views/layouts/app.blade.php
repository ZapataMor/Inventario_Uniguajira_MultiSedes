<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', $branding?->app_name ?? config('app.name', 'Inventario Uniguajira'))</title>
    <link rel="icon" href="{{ asset($branding?->favicon ?? 'assets/images/favicon-uniguajira-32x32.webp') }}" type="image/png">

    @php
        $assetVersion = static function (string $path): int {
            $fullPath = public_path($path);
            $modifiedAt = is_file($fullPath) ? filemtime($fullPath) : false;

            return $modifiedAt ?: time();
        };
    @endphp

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="{{ asset('assets/css/get.css') }}?v={{ $assetVersion('assets/css/get.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/components.css') }}?v={{ $assetVersion('assets/css/components.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/components/get.css') }}?v={{ $assetVersion('assets/css/components/get.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/responsive/get.css') }}?v={{ $assetVersion('assets/css/responsive/get.css') }}">
    @stack('styles')

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-white">
    @include('layouts.navbar')
    @include('layouts.sidebar')

    <main id="main" class="relative isolate min-h-[calc(100vh-4.8rem)]">
        <div
            class="pointer-events-none absolute inset-0 z-0 [background-image:linear-gradient(45deg,transparent_49%,#e5e7eb_49%,#e5e7eb_51%,transparent_51%),linear-gradient(-45deg,transparent_49%,#e5e7eb_49%,#e5e7eb_51%,transparent_51%)] [background-size:40px_40px] [-webkit-mask-image:radial-gradient(ellipse_80%_80%_at_100%_100%,#000_50%,transparent_90%)] [mask-image:radial-gradient(ellipse_80%_80%_at_100%_100%,#000_50%,transparent_90%)]"
            aria-hidden="true">
        </div>
        <div id="toastContainer" class="toast-container"></div>
        {{-- loader shown while AJAX content loads --}}
        <div id="loader" class="loader"></div>
        <div id="main-content" class="relative z-10">
            @yield('content')
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.23/jspdf.plugin.autotable.min.js"></script>

    <script src="{{ asset('assets/js/sidebar.js') }}"></script>
    <script src="{{ asset('assets/js/navbar.js') }}"></script>
    <script src="{{ asset('assets/js/helpers/submitForm.js') }}"></script>
    <script src="{{ asset('assets/js/helpers/delete.js') }}"></script>
    <script src="{{ asset('assets/js/helpers/search.js') }}"></script>
    <script src="{{ asset('assets/js/helpers/toast.js') }}"></script>
    <script src="{{ asset('assets/js/helpers/modal.js') }}"></script>
    <script src="{{ asset('assets/js/helpers/selection.js') }}"></script>
    <script src="{{ asset('assets/js/helpers/autocomplete.js') }}"></script>
    <script src="{{ asset('assets/js/helpers/excel-ui.js') }}?v=1"></script>
    <script src="{{ asset('assets/js/history-guard.js') }}?v=1"></script>
    <script src="{{ asset('assets/js/tasks.js') }}"></script>

    <script src="{{ asset('assets/js/goods.js') }}?v=3"></script>
    @if(Auth::user()->isAdministrator())
        <script src="{{ asset('assets/js/goods-excel-upload.js') }}?v=3"></script>
        <script src="{{ asset('assets/js/goods-excel-upload-global.js') }}?v=5"></script>
    @endif

    <script src="{{ asset('assets/js/user.js') }}"></script>
    <script src="{{ asset('assets/js/profile.js') }}"></script>
    <script src="{{ asset('assets/js/inventory/inventory.js') }}"></script>
    <script src="{{ asset('assets/js/inventory/groups.js') }}?v=groups-search-autoboot-{{ $assetVersion('assets/js/inventory/groups.js') }}"></script>
    <script src="{{ asset('assets/js/inventory/goodsInventory.js') }}?v={{ $assetVersion('assets/js/inventory/goodsInventory.js') }}"></script>
    @if(Auth::user()->isAdministrator())
        <script src="{{ asset('assets/js/inventory/goods-inventory-excel-upload.js') }}?v=6"></script>
    @endif
    <script src="{{ asset('assets/js/inventory/goodsSerialsInventory.js') }}?v={{ $assetVersion('assets/js/inventory/goodsSerialsInventory.js') }}"></script>
    <script src="{{ asset('assets/js/inventory/formGoodInventory.js') }}"></script>
    <script src="{{ asset('assets/js/reports/folders.js') }}"></script>
    <script src="{{ asset('assets/js/reports/reports.js') }}"></script>
    <script src="{{ asset('assets/js/records/historial.js') }}"></script>
    <script src="{{ asset('assets/js/inventory/removedGoods.js') }}"></script>  {{-- ✅ AGREGADO --}}
    <script>
        (() => {
            if (window.inventoryControlCompatibilityBound) {
                return;
            }

            window.inventoryControlCompatibilityBound = true;

            const openQuantityMoveFallback = () => {
                if (typeof selectedItem === 'undefined' || !selectedItem || selectedItem.type !== 'good') {
                    showToast({ success: false, message: 'No se ha seleccionado un bien.' });
                    return;
                }

                const card = selectedItem.element;
                const idInventario = document.getElementById('inventory-name').getAttribute('data-id');
                const assetType = card.dataset.assetType;

                document.getElementById('moverBienId').value = selectedItem.id;
                document.getElementById('moverInventarioOrigenId').value = idInventario;
                document.getElementById('moverNombreBien').value = selectedItem.name;

                const cantidadSection = document.getElementById('moverCantidadSection');
                const cantidadInput = document.getElementById('moverCantidad');

                if (assetType === 'Cantidad') {
                    cantidadSection.style.display = '';
                    document.getElementById('moverCantidadDisponible').value = card.dataset.cantidad;
                    cantidadInput.setAttribute('max', card.dataset.cantidad);
                    cantidadInput.value = 1;
                    cantidadInput.required = true;
                } else {
                    cantidadSection.style.display = 'none';
                    cantidadInput.required = false;
                    cantidadInput.value = '';
                }

                const selectGrupo = document.getElementById('moverGrupoDestino');
                const selectInventario = document.getElementById('moverInventarioDestino');

                selectGrupo.value = '';
                selectGrupo.innerHTML = '<option value="">Seleccionar grupo...</option>';
                selectInventario.innerHTML = '<option value="">Seleccionar inventario...</option>';
                selectInventario.disabled = true;
                selectGrupo.onchange = function () {
                    const grupoId = this.value;
                    selectInventario.innerHTML = '<option value="">Seleccionar inventario...</option>';
                    selectInventario.disabled = true;

                    if (!grupoId) return;

                    fetch('/api/inventories/getByGroupId/' + grupoId, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    })
                        .then(response => response.json())
                        .then(data => {
                            const inventarios = Array.isArray(data) ? data : (data.inventories ?? []);

                            inventarios.forEach(inventory => {
                                if (String(inventory.id) === String(idInventario)) return;
                                const option = document.createElement('option');
                                option.value = inventory.id;
                                option.textContent = inventory.nombre;
                                selectInventario.appendChild(option);
                            });

                            selectInventario.disabled = false;
                        })
                        .catch(() => showToast({ success: false, message: 'Error al cargar inventarios.' }));
                };

                fetch('/api/groups/getAll', {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                    .then(response => response.json())
                    .then(data => {
                        const grupos = Array.isArray(data) ? data : (data.groups ?? []);

                        grupos.forEach(group => {
                            const option = document.createElement('option');
                            option.value = group.id;
                            option.textContent = group.nombre;
                            selectGrupo.appendChild(option);
                        });
                    })
                    .catch(() => showToast({ success: false, message: 'Error al cargar grupos.' }));

                mostrarModal('#modalCambiarInventarioBien');
            };

            const getSerialInventoryContext = () => {
                const serialInventoryName = document.getElementById('good-serial-inventory-name');
                const currentUrl = serialInventoryName?.getAttribute('data-url') || '';
                const inventoryId = serialInventoryName?.dataset.inventoryId
                    || currentUrl.match(/inventory\/(\d+)/)?.[1]
                    || '';

                return { serialInventoryName, currentUrl, inventoryId };
            };

            const openSerialMoveFallback = () => {
                if (typeof selectedItem === 'undefined' || !selectedItem || selectedItem.type !== 'serial-good') {
                    showToast({ success: false, message: 'No se ha seleccionado un bien serial.' });
                    return;
                }

                const card = selectedItem.element;
                const { inventoryId } = getSerialInventoryContext();

                document.getElementById('moverSerialEquipoId').value = card.dataset.id;
                document.getElementById('moverSerialNombreBien').value = card.dataset.name || selectedItem.name || '';
                document.getElementById('moverSerialSerial').value = card.dataset.serial || '';
                document.getElementById('moverSerialMarca').value = card.dataset.brand || '';

                const selectGrupo = document.getElementById('moverSerialGrupoDestino');
                const selectInventario = document.getElementById('moverSerialInventarioDestino');

                selectGrupo.value = '';
                selectGrupo.innerHTML = '<option value="">Seleccionar grupo...</option>';
                selectInventario.innerHTML = '<option value="">Seleccionar inventario...</option>';
                selectInventario.disabled = true;
                selectGrupo.onchange = function () {
                    const grupoId = this.value;
                    selectInventario.innerHTML = '<option value="">Seleccionar inventario...</option>';
                    selectInventario.disabled = true;

                    if (!grupoId) return;

                    fetch('/api/inventories/getByGroupId/' + grupoId, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    })
                        .then(response => response.json())
                        .then(data => {
                            const inventarios = Array.isArray(data) ? data : (data.inventories ?? []);

                            inventarios.forEach(inventory => {
                                if (inventoryId && String(inventory.id) === String(inventoryId)) return;
                                const option = document.createElement('option');
                                option.value = inventory.id;
                                option.textContent = inventory.nombre;
                                selectInventario.appendChild(option);
                            });

                            selectInventario.disabled = false;
                        })
                        .catch(() => showToast({ success: false, message: 'Error al cargar inventarios.' }));
                };

                fetch('/api/groups/getAll', {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                    .then(response => response.json())
                    .then(data => {
                        const grupos = Array.isArray(data) ? data : (data.groups ?? []);

                        grupos.forEach(group => {
                            const option = document.createElement('option');
                            option.value = group.id;
                            option.textContent = group.nombre;
                            selectGrupo.appendChild(option);
                        });
                    })
                    .catch(() => showToast({ success: false, message: 'Error al cargar grupos.' }));

                mostrarModal('#modalCambiarInventarioSerial');
            };

            window.openSerialMoveFallback = openSerialMoveFallback;

            const actions = {
                'cambiar-inventario': () => {
                    if (typeof window.btnCambiarInventario === 'function') {
                        window.btnCambiarInventario();
                        return;
                    }

                    openQuantityMoveFallback();
                },
                'dar-baja-cantidad': () => window.btnDarDeBajaBienCantidad?.(),
                'editar-cantidad': () => window.btnEditarBienCantidad?.(),
                'eliminar-cantidad': () => window.btnEliminarBienCantidad?.(),
                'cambiar-inventario-serial': () => {
                    if (typeof window.btnCambiarInventarioSerial === 'function') {
                        window.btnCambiarInventarioSerial();
                        return;
                    }

                    openSerialMoveFallback();
                },
                'dar-baja-serial': () => window.btnDarDeBajaBienSerial?.(),
                'editar-serial': () => window.btnEditarBienSerial?.(),
                'eliminar-serial': () => window.btnEliminarBienSerial?.(),
            };

            document.addEventListener('click', (event) => {
                const button = event.target.closest('[data-action]');
                const action = button ? actions[button.dataset.action] : null;

                if (!action) {
                    return;
                }

                event.preventDefault();
                event.stopImmediatePropagation();
                action();
            }, true);

            document.addEventListener('submit', (event) => {
                const quantityForm = event.target.closest('#formCambiarInventarioBien');
                const serialForm = event.target.closest('#formCambiarInventarioSerial');
                const form = quantityForm || serialForm;

                if (!form) {
                    return;
                }

                event.preventDefault();
                event.stopImmediatePropagation();

                const submitButton = form.querySelector('button[type="submit"]');
                const originalText = submitButton ? submitButton.innerHTML : '';

                if (submitButton) {
                    submitButton.disabled = true;
                    submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';
                }

                fetch(form.getAttribute('action'), {
                    method: form.getAttribute('method') || 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                    body: new FormData(form),
                })
                    .then(response => response.json())
                    .then(response => {
                        showToast(response);

                        if (!response.success) {
                            return;
                        }

                        if (quantityForm) {
                            ocultarModal('#modalCambiarInventarioBien');

                            const inventoryName = document.getElementById('inventory-name');
                            const groupId = inventoryName.getAttribute('data-group-id');
                            const inventoryId = inventoryName.getAttribute('data-id');

                            loadContent('/group/' + groupId + '/inventory/' + inventoryId, {
                                onSuccess: () => initGoodsInventoryFunctions()
                            });
                            return;
                        }

                        ocultarModal('#modalCambiarInventarioSerial');

                        const { currentUrl } = getSerialInventoryContext();
                        if (!currentUrl) {
                            return;
                        }

                        loadContent(currentUrl, {
                            onSuccess: () => window.initGoodsSerialsInventoryFunctions?.()
                        });
                    })
                    .catch(() => showToast({
                        success: false,
                        message: serialForm ? 'Error al cambiar el serial de inventario.' : 'Error al cambiar el inventario.'
                    }))
                    .finally(() => {
                        if (submitButton) {
                            submitButton.disabled = false;
                            submitButton.innerHTML = originalText;
                        }
                    });
            }, true);
        })();
    </script>
    {{-- <script src="{{ asset('assets/js/onLoaded.js') }}"></script> --}}
    @stack('scripts')
</body>
</html>
