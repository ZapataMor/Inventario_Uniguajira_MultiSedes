<div class="sidebar" id="sidebar">
    <nav>
        <ul class="list-unstyled">
            <li>
                <a id="home" href="{{ route('home.index') }}" data-nav>
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
</div>
