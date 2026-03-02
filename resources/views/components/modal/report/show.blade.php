@extends('layouts.app')

@section('title', 'Reportes')

@section('content')
<div class="container content">

    <div class="report-back-and-title">
        <a href="{{ route('reports.index') }}" class="btn-back">
            ← Volver
        </a>
        <h2>{{ $folder->name }}</h2>
    </div>

    <div class="report-grid">
        @forelse($reports as $report)
            <div class="report-item-card">

                <div class="report-folder-left">
                    <i class="fas fa-file-pdf fa-2x"></i>
                </div>

                <div class="report-folder-center">
                    <div class="title name-item">
                        {{ $report->name }}
                    </div>
                    <small>
                        <i class="fas fa-calendar-alt"></i>
                        {{ $report->created_at->format('Y-m-d') }}
                    </small>
                </div>

                <div class="report-folder-right">
                    <button class="btn-open"
                        onclick='downloadReport(event, {{ $report->id }}, @json($report->name))'>
                        <i class="fas fa-download"></i> Descargar
                    </button>
                </div>

            </div>
        @empty
            <p>No hay reportes en esta carpeta</p>
        @endforelse
    </div>

</div>
@endsection
