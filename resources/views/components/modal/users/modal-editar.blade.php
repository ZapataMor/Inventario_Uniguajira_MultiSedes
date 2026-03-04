{{-- Modal Editar Usuario - Flyout Panel --}}

<div id="modalEditarUsuario" class="modal flyout-modal">
    <div class="flyout-panel">
        <span class="close" onclick="ocultarModal('#modalEditarUsuario')">&times;</span>
        <h2>Editar Usuario</h2>

        <form id="formEditarUser" class="form-container" method="POST"
            action="{{ url('/api/users/update') }}" autocomplete="off">
            @csrf

            <input type="hidden" id="edit-id" name="id">

            <div class="form-section">
                <div class="section-header">Datos del Usuario</div>

                <div class="form-fields-grid">
                    <div class="form-field-full">
                        <label for="edit-nombre" class="form-label">Nombre Completo:</label>
                        <input type="text" id="edit-nombre" name="name"
                            class="form-input" required />
                    </div>

                    <div>
                        <label for="edit-nombre_usuario" class="form-label">Nombre de Usuario:</label>
                        <input type="text" id="edit-nombre_usuario" name="username"
                            class="form-input" required />
                    </div>

                    <div>
                        <label for="edit-email" class="form-label">Correo Electrónico:</label>
                        <input type="email" id="edit-email" name="email"
                            class="form-input" required />
                    </div>

                    <div>
                        <label for="edit-role" class="form-label">Rol:</label>
                        <select id="edit-role" name="role" class="form-input" required>
                            <option value="">Selecciona un rol</option>
                            <option value="administrador">Administrador</option>
                            <option value="consultor">Consultor</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <div class="section-header">Cambiar Contraseña</div>

                <div class="form-fields-grid">
                    <div class="form-field-full">
                        <label for="edit-password" class="form-label">Nueva Contraseña:</label>
                        <input type="password" id="edit-password" name="password"
                            class="form-input" placeholder="Dejar vacío para no cambiar" />
                        <small style="color: #6b7280; font-size: 12px; margin-top: 4px; display: block;">
                            Solo completa este campo si deseas cambiar la contraseña.
                        </small>
                    </div>
                </div>
            </div>

            <div class="form-actions" style="margin-top: 20px;">
                <button type="submit" class="btn submit-btn">
                    <i class="fas fa-rotate"></i> Actualizar
                </button>
            </div>
        </form>
    </div>
</div>