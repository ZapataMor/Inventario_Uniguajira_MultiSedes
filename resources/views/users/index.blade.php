@extends('layouts.app')

@section('title', 'Usuarios')

@section('content')
<div class="content">

    <div class="inventory-header">
        <h1>Usuarios</h1>
    </div>

    <div class="w-full max-w-7xl mx-auto px-4">
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
            <div class="overflow-x-auto">
                <table class="tabla w-full table-auto" id="tableBody">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200">
                            <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wide w-12">
                                N°
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wide w-32">
                                Nombre
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wide w-24">
                                Usuario
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wide min-w-0 w-full">
                                Email
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wide w-24">
                                Rol
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wide w-24">
                                Registrado
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wide w-28">
                                Último Acceso
                            </th>
                            <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wide w-20">
                                Acciones
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $user)
                            @php $isAdmin = $user->role === 'administrador'; @endphp
                            <tr class="border-t border-gray-100 hover:bg-gray-50 transition-colors duration-100" data-search="{{ strtolower($user->name . ' ' . $user->username . ' ' . $user->email) }}">

                                {{-- # (ID) --}}
                                <td class="px-4 py-3 text-center whitespace-nowrap">
                                    <div class="text-sm text-gray-700 font-medium">
                                        {{ $user->id }}
                                    </div>
                                </td>

                                {{-- Nombre --}}
                                <td class="px-4 py-3">
                                    <div class="text-sm font-semibold text-gray-900">
                                        {{ $user->name }}
                                    </div>
                                </td>

                                {{-- Usuario --}}
                                <td class="px-4 py-3">
                                    <div class="text-sm text-gray-600">
                                        {{ $user->username }}
                                    </div>
                                </td>

                                {{-- Email --}}
                                <td class="px-4 py-3">
                                    <div class="text-sm text-gray-700">
                                        {{ $user->email }}
                                    </div>
                                </td>

                                {{-- Rol --}}
                                <td class="px-4 py-3">
                                    <span class="inline-flex align-items-center px-2.5 py-0.5 rounded-md text-xs font-semibold {{ $isAdmin ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700' }} border {{ $isAdmin ? 'border-purple-200' : 'border-blue-200' }}">
                                        {{ ucfirst($user->role) }}
                                    </span>
                                </td>

                                {{-- Registrado --}}
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="text-sm text-gray-700">
                                        {{ $user->created_at?->format('d/m/Y') }}
                                    </div>
                                </td>

                                {{-- Último Acceso --}}
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="text-sm text-gray-700">
                                        @if($user->last_login_at)
                                            <div class="text-sm text-gray-700 font-medium">
                                                {{ $user->last_login_at->format('d/m/Y') }}
                                            </div>
                                            <div class="text-xs text-gray-400">
                                                {{ $user->last_login_at->format('g:i A') }}
                                            </div>
                                        @else
                                            <span class="text-gray-400 italic">Nunca</span>
                                        @endif
                                    </div>
                                </td>

                                {{-- Acciones --}}
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2 justify-center">
                                        <button
                                            class="inline-flex items-center justify-center w-8 h-8 rounded-md bg-red-700 text-white hover:bg-red-800 transition-colors duration-200"
                                            title="Editar usuario"
                                            data-id="{{ $user->id }}"
                                            data-nombre="{{ $user->name }}"
                                            data-nombre-usuario="{{ $user->username }}"
                                            data-email="{{ $user->email }}"
                                            data-role="{{ $user->role }}"
                                            onclick="btnEditarUser(this)"
                                        >
                                            <i class="fas fa-pen text-xs"></i>
                                        </button>

                                        @if($user->name !== 'Administrador')
                                            <a class="btn-eliminar"
                                            onclick="eliminarUsuario({{ $user->id }})">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        @endif
                                    </div>
                                </td>

                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-16 text-center text-gray-400">
                                    <i class="fas fa-users text-5xl block mb-3 opacity-40"></i>
                                    <p class="m-0 text-base font-medium">No hay usuarios registrados</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- MODALES --}}
    @include('components.modal.users.modal-crear')
    @include('components.modal.users.modal-editar')



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


    <script>
        document.addEventListener('DOMContentLoaded', () => {
            initUserFunctions();
        });
    </script>

</div>
@endsection