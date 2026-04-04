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

<header>
    <div class="left">
        <div class="menu-container">
            <div class="menu" id="menu">
                <div></div>
                <div></div>
                <div></div>
            </div>
        </div>
        <div class="brand">
            <img src="{{ asset($branding?->logo_navbar ?? 'assets/images/logo-uniguajira-blanco.webp') }}" alt="{{ $branding?->sede_name ?? 'Logo' }}" class="logo">
        </div>
    </div>

    <div class="right">
        <a href="{{ route('semillero.index') }}" aria-label="Ir a semillero" class="sem-link">
            <img src="{{ asset($branding?->extra['logo_secondary_navbar'] ?? 'assets/images/Diseño4-1.png') }}" alt="logo 2" class="logo-sem">
        </a>
        <button
            type="button"
            data-user-trigger
            class="m-2 flex h-12 w-12 select-none items-center justify-center rounded-full border border-black text-xl font-extrabold leading-none shadow-sm transition-transform duration-200 hover:scale-110 focus:outline-none focus:ring-2 focus:ring-white/80 {{ $avatarColorClass }}"
            onclick="toggleUserMenu()"
            aria-label="Abrir menu de usuario"
        >
            {{ $profileInitial }}
        </button>
    </div>
</header>

<div class="user-menu" style="min-width: 200px;">
    <div id="userMenu" class="user-menu-content hidden">
        <div class="user-menu-account">
            <p class="user-menu-name">{{ auth()->user()->name }}</p>
            <p class="user-menu-handle">{{ '@' . auth()->user()->username }}</p>
        </div>

        <button class="user-menu-item"
            type="button"
            onclick="openProfile(event)"
        >
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

