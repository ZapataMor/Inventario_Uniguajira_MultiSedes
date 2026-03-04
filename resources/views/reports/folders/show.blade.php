@extends('layouts.app')

@section('title', 'Reportes de carpeta')

@section('content')
<div class="container content">
    <div class="report-back-and-title">
        <div class="location">{{ $folder->name }}</div>
        <button class="report-btn-back" onclick="loadContent('{{ route('reports.index') }}', { onSuccess: () => initReportsModule() })">
            <i class="fas fa-arrow-left"></i> Volver
        </button>
    </div>

    <div class="report-item-grid">
        @include('reports.folders.reports-list', ['reports' => $reports])
    </div>
</div>
@endsection

