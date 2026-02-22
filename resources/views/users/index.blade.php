@extends('layouts.app')

@section('title', 'Usuarios')

@section('content')
<div class="content">

    <div class="inventory-header">
        <h1>Usuarios</h1>
    </div>

    {{-- Top Bar --}}
    <x-generals.top-bar
        id="searchInput"
        placeholder="Buscar usuario..."
        modal="#modalCrearUsuario"
    />

    {{-- Tabla de usuarios --}}
    @if($users->isEmpty())
        <div class="empty-state">
            <i class="fas fa-users fa-3x"></i>
            <p>No hay usuarios registrados</p>
        </div>
    @else
        <div class="users-table-wrapper">
            <table class="users-table" id="tableBody">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Usuario</th>
                        <th>Email</th>
                        <th>Rol</th>
                        <th>Registrado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($users as $user)
                        @php $isAdmin = $user->role === 'administrador'; @endphp
                        <tr class="card-item" data-search="{{ strtolower($user->name . ' ' . $user->username . ' ' . $user->email) }}">

                            <td class="user-id">#{{ $user->id }}</td>

                            <td class="user-name">{{ $user->name }}</td>

                            <td class="user-username">{{ $user->username }}</td>

                            <td class="user-email">{{ $user->email }}</td>

                            <td>
                                <span class="user-role-badge {{ $isAdmin ? 'role-admin' : 'role-consultor' }}">
                                    {{ ucfirst($user->role) }}
                                </span>
                            </td>

                            <td class="user-date">
                                {{ $user->created_at?->format('d/m/Y') }}
                            </td>

                            <td>
                                <div class="user-actions">
                                    <button
                                        class="action-btn action-btn-edit"
                                        title="Editar usuario"
                                        data-id="{{ $user->id }}"
                                        data-nombre="{{ $user->name }}"
                                        data-nombre-usuario="{{ $user->username }}"
                                        data-email="{{ $user->email }}"
                                        data-role="{{ $user->role }}"
                                        onclick="btnEditarUser(this)"
                                    >
                                        <i class="fas fa-pen"></i>
                                    </button>

                                    <button
                                        class="action-btn action-btn-delete"
                                        title="Eliminar usuario"
                                        onclick="mostrarConfirmacion({{ $user->id }})"
                                    >
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>

                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    {{-- MODALES --}}
    @include('users.modal-crear')
    @include('users.modal-editar')
    @include('users.modal-confirmar-eliminar')

    @once
    <style>
        /* ── Wrapper ────────────────────────────────────────────── */
        .users-table-wrapper {
            background: #fff;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            overflow: hidden;
            box-shadow: 0 1px 4px rgba(0,0,0,0.06);
        }

        /* ── Tabla ──────────────────────────────────────────────── */
        .users-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        /* ── Cabecera ───────────────────────────────────────────── */
        .users-table thead tr {
            border-bottom: 2px solid #e5e7eb;
        }

        .users-table thead th {
            padding: 14px 20px;
            text-align: left;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.07em;
            color: #6b7280;
            text-transform: uppercase;
            white-space: nowrap;
        }

        /* ── Filas ──────────────────────────────────────────────── */
        .users-table tbody tr {
            border-bottom: 1px solid #f3f4f6;
            transition: background 0.15s;
        }

        .users-table tbody tr:last-child {
            border-bottom: none;
        }

        .users-table tbody tr:hover {
            background: #f9fafb;
        }

        .users-table tbody td {
            padding: 14px 20px;
            color: #374151;
            vertical-align: middle;
        }

        /* ── Celdas específicas ─────────────────────────────────── */
        .user-id {
            font-weight: 600;
            color: #9ca3af !important;
            white-space: nowrap;
        }

        .user-name {
            font-weight: 600;
            color: #111827 !important;
        }

        .user-username,
        .user-email,
        .user-date {
            color: #6b7280 !important;
        }

        /* ── Badge de rol ───────────────────────────────────────── */
        .user-role-badge {
            display: inline-flex;
            align-items: center;
            padding: 3px 12px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
            white-space: nowrap;
        }

        .role-admin {
            background: #ede9fe;
            color: #5b21b6;
        }

        .role-consultor {
            background: #dbeafe;
            color: #1e40af;
        }

        /* ── Acciones ───────────────────────────────────────────── */
        .user-actions {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .action-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 34px;
            height: 34px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 13px;
            transition: background 0.2s, color 0.2s;
        }

        .action-btn-edit {
            background: #a3333d;
            color: #fff;
        }

        .action-btn-edit:hover {
            background: #8c2b32;
        }

        .action-btn-delete {
            background: #fee2e2;
            color: #dc2626;
        }

        .action-btn-delete:hover {
            background: #fecaca;
        }

        /* ── Responsive ─────────────────────────────────────────── */
        @media (max-width: 768px) {
            .users-table thead th:nth-child(3),
            .users-table tbody td:nth-child(3),
            .users-table thead th:nth-child(4),
            .users-table tbody td:nth-child(4),
            .users-table thead th:nth-child(6),
            .users-table tbody td:nth-child(6) {
                display: none;
            }
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            initUserFunctions();
        });
    </script>
    @endonce

</div>
@endsection