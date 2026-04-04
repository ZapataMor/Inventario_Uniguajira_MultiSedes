@extends('layouts.app')

@section('title', 'Mi perfil')

@section('content')
@php
    $defaultPhoto = asset('assets/uploads/img/users/defaultProfile.jpg');
    $photoPath = $user->profile_photo_path ? asset($user->profile_photo_path) : $defaultPhoto;
    $roleLabel = $user->displayRole();
    $emailStatus = $user->email_verified_at ? 'Verificado' : 'Pendiente';
@endphp

<div class="content profile-page">
    <div class="profile-header">
        <div>
            <h1>Mi perfil</h1>
            <p>Consulta y actualiza los datos principales de tu cuenta.</p>
        </div>
    </div>

    <div class="profile-grid">
        <section class="profile-card profile-summary-card">
            <div class="profile-summary-top">
                <img
                    src="{{ $photoPath }}"
                    alt="Foto de perfil de {{ $user->name }}"
                    class="profile-avatar"
                    onerror="this.src='{{ $defaultPhoto }}'"
                >

                <div class="profile-identity">
                    <span class="profile-chip {{ $user->isAdministrator() ? 'chip-admin' : 'chip-consultor' }}">
                        {{ $roleLabel }}
                    </span>

                    <h2>{{ $user->name }}</h2>
                    <p>{{ '@' . $user->username }}</p>
                </div>
            </div>

            <dl class="profile-details">
                <div class="profile-detail-item">
                    <dt>Correo institucional</dt>
                    <dd>{{ $user->email }}</dd>
                </div>

                <div class="profile-detail-item">
                    <dt>Estado del correo</dt>
                    <dd>
                        <span class="profile-chip {{ $user->email_verified_at ? 'chip-success' : 'chip-warning' }}">
                            {{ $emailStatus }}
                        </span>
                    </dd>
                </div>

                <div class="profile-detail-item">
                    <dt>Fecha de registro</dt>
                    <dd>{{ $user->created_at?->timezone('America/Bogota')->format('d/m/Y h:i A') ?? 'Sin registro' }}</dd>
                </div>

                <div class="profile-detail-item">
                    <dt>Ultimo acceso</dt>
                    <dd>{{ $user->last_login_at?->timezone('America/Bogota')->format('d/m/Y h:i A') ?? 'Sin registros' }}</dd>
                </div>
            </dl>
        </section>

        <div class="profile-stack">
            <section class="profile-card">
                <div class="profile-card-header">
                    <div>
                        <h2>Informacion de la cuenta</h2>
                        <p>Edita tu nombre, usuario y correo. El rol es solo de consulta.</p>
                    </div>
                </div>

                <form id="formEditarPerfil" action="{{ route('profile.update') }}" method="POST" class="profile-form">
                    @csrf

                    <div class="profile-form-grid">
                        <label class="profile-field">
                            <span>Nombre completo</span>
                            <input
                                type="text"
                                name="name"
                                value="{{ $user->name }}"
                                maxlength="255"
                                autocomplete="name"
                                required
                            >
                        </label>

                        <label class="profile-field">
                            <span>Nombre de usuario</span>
                            <input
                                type="text"
                                name="username"
                                value="{{ $user->username }}"
                                maxlength="255"
                                autocomplete="username"
                                required
                            >
                        </label>

                        <label class="profile-field profile-field-full">
                            <span>Correo electronico</span>
                            <input
                                type="email"
                                name="email"
                                value="{{ $user->email }}"
                                maxlength="255"
                                autocomplete="email"
                                required
                            >
                        </label>

                        <label class="profile-field profile-field-full">
                            <span>Rol asignado</span>
                            <input
                                type="text"
                                value="{{ $roleLabel }}"
                                disabled
                                readonly
                            >
                        </label>
                    </div>

                    <div class="profile-actions">
                        <p class="profile-note">Si cambias el correo, su estado pasara a pendiente.</p>

                        <button type="submit" class="profile-submit-button">
                            Guardar cambios
                        </button>
                    </div>
                </form>
            </section>

            <section class="profile-card">
                <div class="profile-card-header">
                    <div>
                        <h2>Seguridad</h2>
                        <p>Actualiza tu contrasena desde esta misma vista.</p>
                    </div>
                </div>

                <form id="formCambiarPassword" action="{{ route('profile.password.update') }}" method="POST" class="profile-form">
                    @csrf

                    <div class="profile-form-grid profile-password-grid">
                        <label class="profile-field profile-field-full">
                            <span>Contrasena actual</span>
                            <input
                                type="password"
                                name="current_password"
                                autocomplete="current-password"
                                required
                            >
                        </label>

                        <label class="profile-field">
                            <span>Nueva contrasena</span>
                            <input
                                type="password"
                                name="password"
                                autocomplete="new-password"
                                required
                            >
                        </label>

                        <label class="profile-field">
                            <span>Confirmar nueva contrasena</span>
                            <input
                                type="password"
                                name="password_confirmation"
                                autocomplete="new-password"
                                required
                            >
                        </label>
                    </div>

                    <div class="profile-actions">
                        <p class="profile-note">Usa una contrasena larga y dificil de adivinar.</p>

                        <button type="submit" class="profile-submit-button">
                            Actualizar contrasena
                        </button>
                    </div>
                </form>
            </section>
        </div>
    </div>
</div>

<style>
    .profile-page {
        max-width: 1180px;
        margin: 0 auto;
        padding: 24px 18px 40px;
    }

    .profile-header {
        margin-bottom: 24px;
    }

    .profile-header h1 {
        margin: 0 0 8px;
        color: #111827;
        font-size: clamp(1.8rem, 2.2vw, 2.4rem);
        font-weight: 800;
        letter-spacing: -0.03em;
    }

    .profile-header p,
    .profile-card-header p,
    .profile-identity p,
    .profile-note {
        margin: 0;
        color: #64748b;
    }

    .profile-grid {
        display: grid;
        grid-template-columns: minmax(280px, 360px) minmax(0, 1fr);
        gap: 22px;
        align-items: start;
    }

    .profile-stack {
        display: grid;
        gap: 22px;
        min-width: 0;
    }

    .profile-card {
        background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
        border: 1px solid #dbe4ee;
        border-radius: 24px;
        box-shadow: 0 18px 40px rgba(15, 23, 42, 0.08);
        padding: 24px;
    }

    .profile-summary-card {
        position: sticky;
        top: 92px;
    }

    .profile-summary-top {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 16px;
        text-align: center;
        padding-bottom: 22px;
        border-bottom: 1px solid #e2e8f0;
    }

    .profile-avatar {
        width: 112px;
        height: 112px;
        object-fit: cover;
        border-radius: 999px;
        border: 4px solid #ffffff;
        box-shadow: 0 12px 24px rgba(15, 23, 42, 0.14);
        background: #e2e8f0;
    }

    .profile-identity h2,
    .profile-card-header h2 {
        margin: 10px 0 4px;
        color: #0f172a;
        font-size: 1.25rem;
        font-weight: 700;
    }

    .profile-details {
        display: grid;
        gap: 14px;
        margin: 22px 0 0;
    }

    .profile-detail-item {
        padding: 14px 16px;
        border-radius: 16px;
        background: rgba(255, 255, 255, 0.72);
        border: 1px solid #e2e8f0;
    }

    .profile-detail-item dt {
        margin-bottom: 6px;
        color: #64748b;
        font-size: 0.83rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .profile-detail-item dd {
        margin: 0;
        color: #0f172a;
        font-size: 0.96rem;
        font-weight: 600;
        word-break: break-word;
    }

    .profile-chip {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 32px;
        padding: 6px 12px;
        border-radius: 999px;
        font-size: 0.8rem;
        font-weight: 700;
        letter-spacing: 0.02em;
    }

    .chip-admin {
        background: #ede9fe;
        color: #6d28d9;
    }

    .chip-consultor {
        background: #dbeafe;
        color: #1d4ed8;
    }

    .chip-success {
        background: #dcfce7;
        color: #166534;
    }

    .chip-warning {
        background: #fef3c7;
        color: #92400e;
    }

    .profile-card-header {
        margin-bottom: 22px;
    }

    .profile-form-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 18px;
    }

    .profile-field {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .profile-field-full {
        grid-column: 1 / -1;
    }

    .profile-field span {
        color: #334155;
        font-size: 0.92rem;
        font-weight: 700;
    }

    .profile-field input {
        width: 100%;
        min-height: 48px;
        padding: 12px 14px;
        border: 1px solid #cbd5e1;
        border-radius: 14px;
        background: #ffffff;
        color: #0f172a;
        font-size: 0.96rem;
        outline: none;
        transition: border-color 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease;
    }

    .profile-field input:focus {
        border-color: #0f766e;
        box-shadow: 0 0 0 4px rgba(15, 118, 110, 0.12);
    }

    .profile-field input[disabled] {
        background: #f8fafc;
        color: #475569;
        cursor: not-allowed;
    }

    .profile-actions {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        margin-top: 24px;
        padding-top: 18px;
        border-top: 1px solid #e2e8f0;
    }

    .profile-submit-button {
        border: 0;
        border-radius: 14px;
        padding: 12px 20px;
        background: linear-gradient(135deg, #0f766e 0%, #155e75 100%);
        color: #ffffff;
        font-size: 0.95rem;
        font-weight: 700;
        cursor: pointer;
        box-shadow: 0 10px 20px rgba(21, 94, 117, 0.18);
        transition: transform 0.2s ease, box-shadow 0.2s ease, opacity 0.2s ease;
    }

    .profile-submit-button:hover {
        transform: translateY(-1px);
        box-shadow: 0 14px 26px rgba(21, 94, 117, 0.22);
    }

    @media (max-width: 1024px) {
        .profile-grid {
            grid-template-columns: 1fr;
        }

        .profile-summary-card {
            position: static;
        }
    }

    @media (max-width: 720px) {
        .profile-page {
            padding-inline: 12px;
        }

        .profile-card {
            padding: 18px;
            border-radius: 20px;
        }

        .profile-form-grid {
            grid-template-columns: 1fr;
        }

        .profile-actions {
            flex-direction: column;
            align-items: stretch;
        }

        .profile-submit-button {
            width: 100%;
        }
    }
</style>
@endsection
