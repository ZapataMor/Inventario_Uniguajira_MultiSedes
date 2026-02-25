<div id="modalCrearUsuario" class="modal flyout-modal">
    <div class="flyout-panel">
        <span class="close" onclick="ocultarModal('#modalCrearUsuario')">&times;</span>
        <h2>Nuevo Usuario</h2>

        <form id="formCrearUser" {{-- class="form-container" --}} method="POST"
            action="{{ url('/api/users/store') }}" autocomplete="off">
            @csrf

            <div class="form-section">
                <div class="section-header">Datos del Usuario</div>

                <div class="form-fields-grid">
                    <div class="form-field-full">
                        <label for="create-nombre" class="form-label">Nombre Completo:</label>
                        <input type="text" id="create-nombre" name="name"
                            class="form-input" placeholder="Juan Pérez" required />
                    </div>
                    <div>
                        <label for="create-username" class="form-label">Nombre de Usuario:</label>
                        <input type="text" id="create-username" name="username"
                            class="form-input" placeholder="juanperez" required />
                    </div>
                    <div>
                        <label for="create-email" class="form-label">Correo Electrónico:</label>
                        <input type="email" id="create-email" name="email"
                            class="form-input" placeholder="juan@example.com" required />
                    </div>
                    <div>
                        <label for="create-password" class="form-label">Contraseña:</label>
                        <input type="password" id="create-password" name="password"
                            class="form-input" placeholder="Mínimo 6 caracteres" required />
                    </div>
                    <div>
                        <label for="create-role" class="form-label">Rol:</label>
                        <select id="create-role" name="role" class="form-input" required>
                            <option value="">Selecciona un rol</option>
                            <option value="administrador">Administrador</option>
                            <option value="consultor">Consultor</option>
                        </select>
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