{{-- Declara las variables de entrada que este componente puede recibir cuando se invoca desde otra vista --}}
{{-- Si no se le pasa ningún valor, utilizará los definidos en este @props --}}
@props([

// Define el identificador que tendrá este campo de búsqueda
    'id' => null,
    'placeholder' => 'Buscar...',

// Define la función JS que se iniciará al hacer click en el botón "Crear"
    'onclick' => null,

// Indica qué modal debe contener el botón, esto se define las funciones JS
    'modal' => null,

// Define si el botón "Crear" debe renderizarse o no
    'canCreate' => "true",
])

{{-- Contenedor que tiene la estructura HTML de la top-bar --}}
<div class="top-bar">

{{-- Contenedor que tiene la barra de búsqueda --}}
    <div class="search-container">
        <input
            id="{{ $id }}"
            type="text"
            placeholder="{{ $placeholder }}"
            class="search-bar searchInput"
        />
        <i class="search-icon fas fa-search"></i>
    </div>

{{-- Define qué botones se mostrarán --}}
    <div class="action-buttons">

{{-- Condicional que define si se mostrará el botón de "Crear" teniendo en cuenta si el usuario es administrador y si se debe renderizar el botón --}}
        @if( $canCreate === "true" && Auth::user()->isAdministrator() )

{{-- Aquí se define qué modal se va a utilizar--}}
            @php

// Si la variable $modalSelector tiene contenido, se mostrará dicho contenido y si no, se mostrará por defecto #modalCrearBien
                $modalSelector = $modal ?? '#modalCrearBien';
            @endphp

{{-- Define qué evento ocurrirá al presionar el botón "Crear" --}}
            <button class="create-btn"
                @if($onclick === 'mostrarModal')
                    onclick="mostrarModal('{{ $modalSelector }}')"

{{-- Si el valor de onclick es nulo o tiene un parámetro diferente, entonces este se ejecutará --}}
                @elseif($onclick)
                    onclick="{{ $onclick }}()"

{{-- A pesar de no haber definido la función para onclick, esta seguirá funcionando de forma normal --}}
                @else
                    onclick="mostrarModal('{{ $modalSelector }}')"
                @endif
            >
                Crear
            </button>
        @endif

{{-- Variable que permite añadir más componentes sin modificar el predefinido --}}
        {{ $slot }}
    </div>
</div>
