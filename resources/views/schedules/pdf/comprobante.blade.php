{{--
    Comprobante de labor realizada.

    Se renderiza con Dompdf, asi que el maquetado usa tablas y no
    flexbox ni grid: son las unicas construcciones que el motor
    resuelve de forma predecible.
--}}
@php
    $imageRows = $images->chunk(2);
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Comprobante {{ $receiptCode }}</title>
    <style>
        @page { margin: 26px 34px 60px; }

        body {
            margin: 0;
            font-family: Helvetica, Arial, sans-serif;
            font-size: 11px;
            color: #334155;
        }

        /* ─── Encabezado institucional ───────────────────────────── */

        .brand { width: 100%; border-collapse: collapse; }
        .brand td { vertical-align: middle; }
        .brand-logo { width: 150px; }
        .brand-logo img { width: 150px; }

        .brand-text { text-align: right; }
        .brand-institution {
            margin: 0;
            font-size: 14px;
            font-weight: bold;
            color: #0f172a;
            letter-spacing: .02em;
        }
        .brand-app { margin: 3px 0 0; font-size: 10.5px; color: #64748b; }
        .brand-sede {
            display: inline-block;
            margin-top: 6px;
            padding: 3px 10px;
            border-radius: 10px;
            background-color: #ecfdf5;
            color: #047857;
            font-size: 10.5px;
            font-weight: bold;
        }

        .rule { height: 3px; background-color: #059669; margin: 12px 0 0; }
        .rule-soft { height: 1px; background-color: #e2e8f0; margin: 0 0 18px; }

        /* ─── Título y folio ─────────────────────────────────────── */

        .title-block { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        .title-block td { vertical-align: middle; }

        .doc-title {
            margin: 0;
            font-size: 17px;
            font-weight: bold;
            color: #0f172a;
            letter-spacing: .04em;
        }
        .doc-subtitle { margin: 4px 0 0; font-size: 10.5px; color: #64748b; }

        .folio {
            width: 190px;
            padding: 9px 12px;
            border: 1px solid #a7f3d0;
            border-radius: 8px;
            background-color: #f0fdf4;
            text-align: center;
        }
        .folio-label {
            margin: 0;
            font-size: 8.5px;
            font-weight: bold;
            color: #047857;
            letter-spacing: .08em;
        }
        .folio-code {
            margin: 3px 0 0;
            font-size: 14px;
            font-weight: bold;
            color: #14532d;
        }

        /* ─── Secciones ──────────────────────────────────────────── */

        .section-title {
            margin: 0 0 7px;
            padding-bottom: 4px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 10px;
            font-weight: bold;
            color: #059669;
            letter-spacing: .1em;
            text-transform: uppercase;
        }

        .data { width: 100%; border-collapse: collapse; margin-bottom: 18px; }
        .data td {
            padding: 7px 10px;
            border: 1px solid #e2e8f0;
            vertical-align: top;
        }
        .data .label {
            width: 30%;
            background-color: #f8fafc;
            font-size: 9.5px;
            font-weight: bold;
            color: #475569;
            letter-spacing: .04em;
            text-transform: uppercase;
        }
        .data .value { color: #1e293b; font-size: 11px; }
        .data .value strong { color: #0f172a; }
        .muted { color: #94a3b8; font-style: italic; }

        .note {
            padding: 10px 12px;
            border: 1px solid #e2e8f0;
            border-left: 3px solid #059669;
            border-radius: 6px;
            background-color: #f8fafc;
            color: #475569;
            line-height: 1.55;
            margin-bottom: 18px;
        }

        /* ─── Evidencias ─────────────────────────────────────────── */

        .evidence { width: 100%; border-collapse: separate; border-spacing: 0 10px; }
        .evidence td { width: 50%; vertical-align: top; padding-right: 10px; }
        .evidence td.last { padding-right: 0; }

        .shot {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 7px;
            background-color: #ffffff;
        }
        .shot img { width: 100%; border-radius: 5px; }
        .shot-index {
            margin: 0 0 5px;
            font-size: 8.5px;
            font-weight: bold;
            color: #047857;
            letter-spacing: .08em;
            text-transform: uppercase;
        }
        .shot-caption {
            margin: 7px 0 0;
            font-size: 10px;
            line-height: 1.45;
            color: #475569;
        }

        /* ─── Firma y pie ────────────────────────────────────────── */

        .sign { width: 100%; border-collapse: collapse; margin-top: 26px; }
        .sign td { width: 50%; padding: 0 18px; text-align: center; vertical-align: bottom; }
        .sign-line { border-top: 1px solid #94a3b8; padding-top: 6px; font-size: 10px; color: #475569; }
        .sign-name { font-weight: bold; color: #1e293b; }

        .foot {
            position: fixed;
            left: 0;
            right: 0;
            bottom: -40px;
            padding-top: 8px;
            border-top: 1px solid #e2e8f0;
            font-size: 8.5px;
            line-height: 1.5;
            color: #94a3b8;
            text-align: center;
        }
    </style>
</head>
<body>

{{-- El pie va fijo para que se repita en todas las páginas del comprobante. --}}
<div class="foot">
    <div>{{ $footerText }}</div>
    <div>{{ $institution }} · {{ $sedeName }} · Comprobante {{ $receiptCode }} · Emitido el {{ $issuedAt }}</div>
</div>

<table class="brand">
    <tr>
        <td class="brand-logo">
            @if($logoDataUri)
                <img src="{{ $logoDataUri }}" alt="{{ $institution }}">
            @endif
        </td>
        <td class="brand-text">
            <p class="brand-institution">{{ Str::upper($institution) }}</p>
            <p class="brand-app">{{ $appName }}</p>
            <span class="brand-sede">{{ $sedeLabel }}</span>
        </td>
    </tr>
</table>

<div class="rule"></div>
<div class="rule-soft"></div>

<table class="title-block">
    <tr>
        <td>
            <h1 class="doc-title">COMPROBANTE DE LABOR REALIZADA</h1>
            <p class="doc-subtitle">
                Constancia del trabajo documentado en la programación de inventarios.
            </p>
        </td>
        <td width="200" align="right">
            <div class="folio">
                <p class="folio-label">COMPROBANTE N.º</p>
                <p class="folio-code">{{ $receiptCode }}</p>
            </div>
        </td>
    </tr>
</table>

<p class="section-title">Programación</p>
<table class="data">
    <tr>
        <td class="label">Sede</td>
        <td class="value"><strong>{{ $sedeName }}</strong></td>
    </tr>
    <tr>
        <td class="label">Nombre de la programación</td>
        <td class="value">{{ $schedule->title }}</td>
    </tr>
    <tr>
        <td class="label">Ubicaciones</td>
        <td class="value">
            @if($locations)
                {{ implode(' · ', $locations) }}
            @else
                <span class="muted">Sin ubicación asignada</span>
            @endif
        </td>
    </tr>
</table>

<p class="section-title">Labor ejecutada</p>
<table class="data">
    <tr>
        <td class="label">Trabajo realizado</td>
        <td class="value"><strong>{{ $entry->work_name }}</strong></td>
    </tr>
    <tr>
        <td class="label">Responsable</td>
        <td class="value">{{ $entry->responsible_name }}</td>
    </tr>
    <tr>
        <td class="label">Inicio</td>
        <td class="value">{{ $entry->started_at?->format('d/m/Y H:i') }}</td>
    </tr>
    <tr>
        <td class="label">Finalización</td>
        <td class="value">{{ $entry->finished_at?->format('d/m/Y H:i') }}</td>
    </tr>
    <tr>
        <td class="label">Duración</td>
        <td class="value"><strong>{{ $entry->duration_label }}</strong></td>
    </tr>
    <tr>
        <td class="label">Registro recibido</td>
        <td class="value">{{ $registeredAt }}</td>
    </tr>
</table>

<p class="section-title">Observaciones</p>
<div class="note">
    @if($entry->description)
        {!! nl2br(e($entry->description)) !!}
    @else
        <span class="muted">No se registraron observaciones.</span>
    @endif
</div>

<p class="section-title">
    Evidencias fotográficas
    @if($images->isNotEmpty())
        ({{ $images->count() }})
    @endif
</p>

@if($images->isEmpty())
    <div class="note"><span class="muted">No se adjuntaron imágenes a esta labor.</span></div>
@else
    <table class="evidence">
        @foreach($imageRows as $row)
            <tr>
                @foreach($row as $index => $image)
                    <td class="{{ $loop->last ? 'last' : '' }}">
                        <div class="shot">
                            <p class="shot-index">Imagen {{ $index + 1 }}</p>
                            <img src="{{ $image['dataUri'] }}" alt="Evidencia {{ $index + 1 }}">
                            @if($image['description'])
                                <p class="shot-caption">{{ $image['description'] }}</p>
                            @endif
                        </div>
                    </td>
                @endforeach

                {{-- Celda de relleno: mantiene la rejilla a dos columnas. --}}
                @if($row->count() === 1)
                    <td class="last"></td>
                @endif
            </tr>
        @endforeach
    </table>
@endif

<table class="sign">
    <tr>
        <td>
            <div class="sign-line">
                <span class="sign-name">{{ $entry->responsible_name }}</span><br>
                Ejecutó la labor
            </div>
        </td>
        <td>
            <div class="sign-line">
                Recibió y verificó<br>
                {{ $appName }} · {{ $sedeLabel }}
            </div>
        </td>
    </tr>
</table>

</body>
</html>
