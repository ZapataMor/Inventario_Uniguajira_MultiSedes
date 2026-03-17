@props([
    'id' => null,
    'placeholder' => 'Buscar...',
    // 'onclick' should be the JS function name (string) or null
    'onclick' => null,
    // 'modal' is the selector to pass when using mostrarModal
    'modal' => null,
    'canCreate' => "true",
])

<div class="top-bar">
    <div class="search-container">
        <input
            id="{{ $id }}"
            type="text"
            placeholder="{{ $placeholder }}"
            class="search-bar searchInput"
        />
        <i class="search-icon fas fa-search"></i>
    </div>

    <div class="action-buttons">
        @if( $canCreate === "true" && Auth::user()->role === 'administrador' )
            @php
                $modalSelector = $modal ?? '#modalCrearBien';
            @endphp
            <button class="create-btn"
                @if($onclick === 'mostrarModal')
                    onclick="mostrarModal('{{ $modalSelector }}')"
                @elseif($onclick)
                    onclick="{{ $onclick }}()"
                @else
                    onclick="mostrarModal('{{ $modalSelector }}')"
                @endif
            >
                Crear
            </button>
        @endif

        {{ $slot }}
    </div>
</div>
