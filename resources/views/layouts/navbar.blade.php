<header class="bg-[linear-gradient(90deg,#E8BE33_0%,#FF672C_25%,#E8BE33_50%,#FF672C_75%,#E8BE33_100%)] bg-[length:200%_100%] animate-navbar-sunset [will-change:background-position]">
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
    </div>
</header>
