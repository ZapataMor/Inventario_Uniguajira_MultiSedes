@once
<style>
    /* ── Flyout Modal ─────────────────────────────────────────────── */
    .modal.flyout-modal {
        position: fixed !important;
        top: 0 !important; left: 0 !important; right: 0 !important; bottom: 0 !important;
        width: 100vw !important; height: 100vh !important;
        background: rgba(0, 0, 0, 0.45);
        z-index: 9999 !important;
        margin: 0 !important; padding: 0 !important;
        opacity: 0; visibility: hidden;
        transition: opacity 0.3s ease, visibility 0.3s ease;
    }
    .modal.flyout-modal.active {
        opacity: 1 !important; visibility: visible !important;
    }
    .modal.flyout-modal > .flyout-panel {
        position: fixed !important;
        top: 0; right: 0; bottom: 0;
        height: 100vh !important;
        width: 42% !important; max-width: 680px !important; min-width: 380px !important;
        background: #fff;
        border-radius: 0 !important;
        overflow-y: auto; overflow-x: hidden;
        margin: 0 !important; padding: 30px 28px !important;
        box-sizing: border-box !important;
        transform: translateX(100%);
        transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        /* will-change: transform; */
        box-shadow: -4px 0 24px rgba(0, 0, 0, 0.14);
    }
    .modal.flyout-modal.active > .flyout-panel {
        transform: translateX(0) !important;
    }
    .flyout-panel > .close {
        position: absolute; top: 18px; right: 22px;
        font-size: 26px; font-weight: 700; color: #9ca3af;
        cursor: pointer; line-height: 1; background: none;
        border: none; padding: 0; transition: color 0.2s; z-index: 10;
    }
    .flyout-panel > .close:hover { color: #111827; }
    .flyout-panel > h2 { margin: 0 0 22px; padding-right: 40px; font-size: 20px; font-weight: 700; }
    @media (max-width: 768px) {
        .modal.flyout-modal > .flyout-panel { width: 92% !important; min-width: 0 !important; }
    }
</style>
@endonce

<div id="Modalfiltrarhistorial" class="modal flyout-modal">
    <div class="flyout-panel modal-content-large">
        
        <button class="close" onclick="ocultarModal('#Modalfiltrarhistorial')">
            &times;
        </button>

        <h2>Filtros del Historial</h2>

        <form id="filterForm"
              method="GET"
              action="{{ route('records.index') }}"
              class="form-container">

            {{-- Sección: Filtros Generales --}}
            <div class="form-section">
                <div class="section-header">
                    Parámetros de Búsqueda
                </div>

                <div class="form-fields-grid">

                    {{-- Usuario --}}
                    <div>
                        <label for="filter-user" class="form-label">
                            Usuario
                        </label>
                        <select id="filter-user"
                                name="user_id"
                                class="form-input">
                            <option value="">Todos los usuarios</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}"
                                    {{ request('user_id') == $user->id ? 'selected' : '' }}>
                                    {{ $user->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Acción --}}
                    <div>
                        <label for="filter-action" class="form-label">
                            Acción
                        </label>
                        <select id="filter-action"
                                name="action"
                                class="form-input">
                            <option value="">Todas</option>
                            @foreach($actions as $action)
                                <option value="{{ $action }}"
                                    {{ request('action') == $action ? 'selected' : '' }}>
                                    {{ ucfirst($action) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Módulo --}}
                    <div>
                        <label for="filter-model" class="form-label">
                            Módulo
                        </label>
                        <select id="filter-model"
                                name="model"
                                class="form-input">
                            <option value="">Todos</option>
                            @foreach($models as $model)
                                <option value="{{ $model }}"
                                    {{ request('model') == $model ? 'selected' : '' }}>
                                    {{ $model }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Desde --}}
                    <div>
                        <label for="filter-date-from" class="form-label">
                            Desde
                        </label>
                        <input type="date"
                               id="filter-date-from"
                               name="date_from"
                               class="form-input"
                               value="{{ request('date_from') }}">
                    </div>

                    {{-- Hasta --}}
                    <div>
                        <label for="filter-date-to" class="form-label">
                            Hasta
                        </label>
                        <input type="date"
                               id="filter-date-to"
                               name="date_to"
                               class="form-input"
                               value="{{ request('date_to') }}">
                    </div>

                </div>
            </div>

            {{-- Botones --}}
            <div class="form-actions">
                <button type="button"
                        class="btn cancel-btn"
                        onclick="ocultarModal('#Modalfiltrarhistorial')">
                    Cerrar
                </button>

                <button type="submit"
                        class="btn submit-btn">
                    Aplicar filtros
                </button>
            </div>

        </form>

    </div>
</div>


