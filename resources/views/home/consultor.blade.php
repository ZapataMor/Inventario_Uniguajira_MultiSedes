@extends('layouts.app')

@section('title', 'Inicio')

@section('content')

<div class="content">
    <h1>¡Bienvenido, {{ Auth::user()->name ?? 'Usuario' }}!</h1>

    @if(Auth::user()->role === 'consultor')
        <div class="info-section">
            <h2 class="section-title">Información del Consultor</h2>
            <p>Esta es la sección dedicada a los consultores. Aquí puedes encontrar información relevante y recursos para ayudarte en tu rol.</p>
            <ul>
                <li>Recurso 1: <a href="#">Enlace al recurso 1</a></li>
                <li>Recurso 2: <a href="#">Enlace al recurso 2</a></li>
                <li>Recurso 3: <a href="#">Enlace al recurso 3</a></li>
            </ul>
        </div>
    @else
        <p>No tienes acceso a esta sección.</p>
    @endif
</div>
@endsection