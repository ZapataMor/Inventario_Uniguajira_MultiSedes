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
            <img src="{{ asset('assets/images/logo-uniguajira-blanco.webp') }}" alt="logo" class="logo">
        </div>
    </div>

    <div class="right">
        <img
            src="{{ asset(Auth::user()->profile_photo_path ?? 'assets/uploads/img/users/defaultProfile.jpg') }}"
            alt="img-user"
            class="user"
            onclick="toggleUserMenu()"
        />
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
