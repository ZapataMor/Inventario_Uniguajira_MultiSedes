@php
    $portalSuperAdminMode = auth()->user()->isSuperAdmin() && ! tenant();
    $availableTenants = $availableTenants ?? collect();
@endphp

<div id="modalCrearUsuario" class="modal flyout-modal">
    <div class="flyout-panel">
        <span class="close" onclick="ocultarModal('#modalCrearUsuario')">&times;</span>
        <h2>Nuevo Usuario</h2>

        <form id="formCrearUser" method="POST" action="{{ url('/api/users/store') }}" autocomplete="off">
            @csrf

            @if(! $portalSuperAdminMode)
                <input type="hidden" name="target_scope" value="current" />
            @endif

            <div class="form-section">
                <div class="section-header">Datos del Usuario</div>

                <div class="form-fields-grid">
                    @if($portalSuperAdminMode)
                        <div class="form-field-full">
                            <label for="create-target-scope" class="form-label">Crear usuario para:</label>
                            <select id="create-target-scope" name="target_scope" class="form-input" required>
                                <option value="portal">Portal (solo super administradores)</option>
                                @foreach($availableTenants as $tenantData)
                                    <option value="tenant:{{ $tenantData['id'] }}">Sede {{ $tenantData['name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div class="form-field-full">
                        <label for="create-nombre" class="form-label">Nombre Completo:</label>
                        <input
                            type="text"
                            id="create-nombre"
                            name="name"
                            class="form-input"
                            placeholder="Juan Perez"
                            required
                        />
                    </div>

                    <div>
                        <label for="create-username" class="form-label">Nombre de Usuario:</label>
                        <input
                            type="text"
                            id="create-username"
                            name="username"
                            class="form-input"
                            placeholder="juanperez"
                            required
                        />
                    </div>

                    <div>
                        <label for="create-email" class="form-label">Correo Electronico:</label>
                        <input
                            type="email"
                            id="create-email"
                            name="email"
                            class="form-input"
                            placeholder="juan@example.com"
                            required
                        />
                    </div>

                    <div>
                        <label for="create-password" class="form-label">Contrasena:</label>
                        <input
                            type="password"
                            id="create-password"
                            name="password"
                            class="form-input"
                            placeholder="Minimo 6 caracteres"
                            minlength="6"
                            required
                        />
                    </div>

                    <div>
                        <label for="create-role" class="form-label">Rol:</label>
                        <select id="create-role" name="role" class="form-input" required>
                            @if($portalSuperAdminMode)
                                <option value="super_administrador">Super Administrador</option>
                                <option value="administrador">Administrador</option>
                                <option value="consultor">Consultor</option>
                            @else
                                <option value="">Selecciona un rol</option>
                                <option value="administrador">Administrador</option>
                                <option value="consultor">Consultor</option>
                            @endif
                        </select>
                        @if($portalSuperAdminMode)
                            <small id="create-role-help" class="mt-1 block text-xs text-slate-500">
                                En portal solo se permite Super Administrador.
                            </small>
                        @endif
                    </div>
                </div>
            </div>

            <div class="form-actions" style="margin-top: 20px;">
                <button type="submit" class="btn submit-btn">
                    <i class="fas fa-floppy-disk"></i> Guardar
                </button>
            </div>
        </form>
    </div>
</div>