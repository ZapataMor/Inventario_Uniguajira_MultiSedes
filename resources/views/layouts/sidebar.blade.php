@php
    $currentUser = auth()->user();
    $profileName = (string) ($currentUser?->name ?: $currentUser?->username);
    $profileInitial = (string) \Illuminate\Support\Str::of($profileName)
        ->trim()
        ->substr(0, 1)
        ->upper();

    if ($profileInitial === '') {
        $profileInitial = 'U';
    }

    $avatarPalette = [
        'bg-emerald-200 text-emerald-900',
        'bg-sky-200 text-sky-900',
        'bg-amber-200 text-amber-900',
        'bg-rose-200 text-rose-900',
        'bg-indigo-200 text-indigo-900',
        'bg-teal-200 text-teal-900',
        'bg-orange-200 text-orange-900',
    ];

    $avatarSeed = (string) ($currentUser?->email ?: $profileName ?: 'user');
    $avatarIndex = (int) (sprintf('%u', crc32($avatarSeed)) % count($avatarPalette));
    $avatarColorClass = $avatarPalette[$avatarIndex];
@endphp

<div class="sidebar" id="sidebar">
    <nav>
        <ul class="list-unstyled">
            <li>
                <a id="home" href="{{ tenant() ? route('home.index') : route('portal.index') }}" data-nav>
                    <img src="{{ asset('assets/icons/home.svg') }}" alt="">
                    <span>Inicio</span>
                </a>
            </li>

            <li>
                <a id="goods" href="{{ route('goods.index') }}" data-nav>
                    <img src="{{ asset('assets/icons/bienes.svg') }}" alt="">
                    <span>Bienes</span>
                </a>
            </li>

            <li>
                <a id="inventories" href="{{ route('inventory.groups') }}" data-nav>
                    <img src="{{ asset('assets/icons/inventario.svg') }}" alt="">
                    <span>Inventarios</span>
                </a>
            </li>

            <li>
                <a id="removed" href="{{ route('removed.index') }}" data-nav>
                    <img src="{{ asset('assets/icons/basura.svg') }}" alt="">
                    <span>Dados de Baja</span>
                </a>
            </li>

            <li>
                <a id="reports" href="{{ route('reports.index') }}" data-nav>
                    <img src="{{ asset('assets/icons/reportes.svg') }}" alt="">
                    <span>Reportes</span>
                </a>
            </li>

            @if(auth()->user()->isAdministrator() || auth()->user()->isSuperAdmin())
                <li>
                    <a id="users" href="{{ route('users.index') }}" data-nav>
                        <img src="{{ asset('assets/icons/usuarios.svg') }}" alt="">
                        <span>Usuarios</span>
                    </a>
                </li>

                <li>
                    <a id="records" href="{{ route('records.index') }}" data-nav>
                        <img src="{{ asset('assets/icons/historial.svg') }}" alt="">
                        <span>Historial</span>
                    </a>
                </li>
            @endif
        </ul>
    </nav>

    <div class="mt-auto border-t border-slate-200 px-2 pt-3">
        <div class="relative flex justify-center">
            <button
                type="button"
                data-user-trigger
                class="mx-auto flex h-12 w-12 select-none items-center justify-center rounded-full border border-black text-xl font-extrabold leading-none shadow-sm transition-transform duration-200 hover:scale-110 focus:outline-none focus:ring-2 focus:ring-white/80 {{ $avatarColorClass }}"
                onclick="toggleUserMenu()"
                aria-label="Abrir menu de usuario"
            >
                {{ $profileInitial }}
            </button>

            <div id="userMenu" class="user-menu-content hidden">
                <div class="user-menu-account">
                    <p class="user-menu-name">{{ auth()->user()->name }}</p>
                    <p class="user-menu-handle">{{ '@' . auth()->user()->username }}</p>
                </div>

                <button class="user-menu-item" type="button" onclick="openProfile(event)">
                    <img class="user-menu-icon" src="{{ asset('assets/icons/editarPerfil.svg') }}" alt="edit">
                    <span>Mi perfil</span>
                </button>

                <form action="{{ route('logout') }}" method="POST" onclick="logout()">
                    @csrf
                    <button type="submit" class="user-menu-item">
                        <img class="user-menu-icon" src="{{ asset('assets/icons/cerrarSesion.svg') }}" alt="logout">
                        <span>Cerrar Sesión</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
