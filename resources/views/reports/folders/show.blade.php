@extends('layouts.app')

@section('title', 'Reportes de carpeta')

@section('content')
@php
    $reportsBackUrl = auth()->user()?->isSuperAdmin()
        ? route('reports.index', ['portal' => 1])
        : route('reports.index');
@endphp
<div class="container content">
    <div class="report-back-and-title">
        <div class="location">{{ $folder->name }}</div>
        <button class="report-btn-back" onclick="loadContent('{{ $reportsBackUrl }}', { onSuccess: () => initReportsModule() })">
            <i class="fas fa-arrow-left"></i> Volver
        </button>
    </div>

    <div class="report-item-grid">
        @include('reports.folders.reports-list', ['reports' => $reports])
    </div>
</div>
@endsection
