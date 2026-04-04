@extends('layouts.app')

@section('title', 'Inicio')

@section('content')
<div class="content">
    <h1>Bienvenido, {{ Auth::user()->name ?? 'Usuario' }}</h1>

    @if(Auth::user()->isConsultor())
        <section class="consultor-home">
            <div class="consultor-home-hero">
                <h2 class="section-title">Que puedes hacer en la plataforma</h2>
                <p>
                    Como consultor puedes consultar informacion del inventario institucional
                    y descargar reportes ya generados.
                </p>
            </div>

            <div class="consultor-home-grid">
                <article class="consultor-home-card">
                    <i class="fas fa-box-open"></i>
                    <h3>Consultar bienes</h3>
                    <p>Revisa el catalogo de bienes y su informacion general.</p>
                </article>

                <article class="consultor-home-card">
                    <i class="fas fa-warehouse"></i>
                    <h3>Explorar inventarios</h3>
                    <p>Visualiza inventarios por grupo y su estado actual.</p>
                </article>

                <article class="consultor-home-card">
                    <i class="fas fa-trash-alt"></i>
                    <h3>Ver dados de baja</h3>
                    <p>Consulta los bienes retirados del inventario.</p>
                </article>

                <article class="consultor-home-card">
                    <i class="fas fa-file-pdf"></i>
                    <h3>Revisar reportes</h3>
                    <p>Accede a carpetas y descarga reportes existentes.</p>
                </article>
            </div>
        </section>
    @else
        <p>No tienes acceso a esta seccion.</p>
    @endif
</div>
@endsection
