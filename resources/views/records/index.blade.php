@extends('layouts.app')

@section('title', 'Historial')

@section('content')
<div class="content">

    <div class="inventory-header">
        <h1>Historial</h1>
            
        {{-- Estadisticas --}}
        @include('records.stats')
    </div>
    
    
    {{-- Filtros para el historial --}}
    @include('records.filters')

    {{-- Tabla de registros --}}


    <div class="w-full max-w-7xl mx-auto px-4">
        <div class="top-bar">
            <div class="search-container">
                <input
                    type="text"
                    id="searchRecordInput"
                    placeholder="Buscar historial"
                    class="search-bar"
                />
                <i class="search-icon fas fa-search"></i>
            </div>

            <!-- Botón de filtro -->
            <button class="create-btn" id="filterBtn"  onclick="mostrarModal('#Modalfiltrarhistorial')"> 
                <i class="fas fa-filter"></i>
                Filtros
            </button>
            <button class="create-btn" id="reportBtn"  onclick="generatePDF()"> 
                <i class="fas fa-file-pdf"></i>
                Reporte
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="tabla w-full table-auto">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200">
                        <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wide w-12">
                            N°
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wide w-14">
                            Tipo
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wide w-24">
                            Usuario
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wide min-w-0 w-full">
                            Descripción
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wide w-35">
                            Módulo
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wide w-36">
                            Fecha / Hora
                        </th>
                    </tr>
                </thead>
        
                <tbody>
                    @forelse($logs as $log)
                        <tr class="border-t border-gray-100 hover:bg-gray-50 transition-colors duration-100">
        
                            {{-- # (ID) --}}
                            <td class="px-4 py-3 text-center whitespace-nowrap">
                                <div class="text-sm text-gray-700 font-medium">
                                    {{ $log->id }}
                                </div>
                            </td>

                            {{-- Ícono --}}
                            <td class="px-4 py-3 text-center">
                                <div class="w-9 h-9 rounded-lg inline-flex items-center justify-center bg-gray-900">
                                    <i class="fa-solid {{ $log->icon }} text-white text-sm"></i>
                                </div>
                            </td>
        
                            {{-- Usuario --}}
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-full bg-gray-700 flex items-center justify-center text-white text-xs font-bold shrink-0">
                                        {{ $log->user?->initials() ?? 'S' }}
                                    </div>
                                    <div>
                                        <div class="text-sm font-semibold text-gray-900">
                                            {{ $log->user?->name ?? 'Sistema' }}
                                        </div>
                                        <div class="text-xs text-gray-400">
                                            {{ $log->user?->username ?? 'system' }}
                                        </div>
                                    </div>
                                </div>
                            </td>
        
                            {{-- Descripción --}}
                            <td class="px-4 py-3">
                                <div class="text-sm text-gray-700">
                                    {{ $log->description }}
                                </div>
                                @if($log->old_values && $log->action === 'update')
                                    <div class="mt-1 flex flex-wrap gap-1">
                                        @foreach(array_keys($log->old_values) as $key)
                                            @if(isset($log->new_values[$key]) && $log->old_values[$key] != $log->new_values[$key])
                                                <span class="inline-block bg-gray-100 border border-gray-200 rounded px-1.5 py-px text-xs text-gray-500 font-mono">
                                                    {{ $key }}: {{ Str::limit((string)$log->old_values[$key], 18) }} → {{ Str::limit((string)$log->new_values[$key], 18) }}
                                                </span>
                                            @endif
                                        @endforeach
                                    </div>
                                @endif
                            </td>
        
                            {{-- Módulo --}}
                            <td class="px-4 py-3">
                                @if($log->model)
                                    <span class="inline-block px-2.5 py-0.5 whitespace-nowrap rounded-md text-xs font-semibold bg-gray-100 text-gray-700 border border-gray-200">
                                        {{ $log->model_label }}
                                    </span>
                                @else
                                    <span class="text-gray-300">—</span>
                                @endif
                            </td>
        
                            {{-- Fecha / Hora --}}
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="text-sm text-gray-700 font-medium">
                                    {{ $log->created_at->format('d/m/Y') }}
                                </div>
                                <div class="text-xs text-gray-400">
                                    {{ $log->created_at->format('g:i A') }}
                                </div>
                            </td>
        
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-16 text-center text-gray-400">
                                <i class="fa-regular fa-folder-open text-5xl block mb-3 opacity-40"></i>
                                <p class="m-0 text-base font-medium">No hay registros de actividad</p>
                                <p class="mt-1.5 mb-0 text-sm opacity-70">Ajusta los filtros para ver resultados</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        
            {{-- Paginación --}}
            @if($logs->hasPages())
                <div class="px-5 py-4 border-t border-gray-200 bg-gray-50">
                    {{ $logs->links() }}
                </div>
            @endif
        </div>
    
    </div>
</div>
@endsection