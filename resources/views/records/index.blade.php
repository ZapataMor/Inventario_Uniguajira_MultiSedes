@extends('layouts.app')

@section('title', 'Historial')

@section('content')
<div class="content">

    <div class="inventory-header"
        style= "margin-bottom: 20px">
        <h1>Historial</h1>
    </div>


    {{-- Vista de las estadisticas --}}

    <div class="contenedor_statistics">
        <div class="statistics_history">

            <div class="statistics_card">
                <div class= "statistics_content">
                    <p class="statistics_text">
                        Total de registros
                    </p>
                    <p class="statistics_number">
                        {{ number_format($logs->total()) }}
                    </p>
                </div>
            </div>
        
            <div class="statistics_card">
                <div class= "statistics_content">
                    <p class="statistics_text">
                        Acciones de hoy
                    </p>
                    <p class="statistics_number">
                        {{ \App\Models\ActivityLog::whereDate('created_at', today())->count() }}
                    </p>
                </div>
            </div>
        
            <div class="statistics_card">
                <div class= "statistics_content">
                    <p class="statistics_text">
                        Usuarios activos
                    </p>
                    <p class="statistics_number">
                        {{ \App\Models\ActivityLog::distinct('user_id')->whereDate('created_at', today())->count('user_id') }}
                    </p>
                </div>
            </div>
        
            <div class="statistics_card">
                <div class= "statistics_content">
                    <p class="statistics_text">
                        Esta semana
                    </p>
                    <p class="statistics_number">
                        {{ \App\Models\ActivityLog::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count() }}
                    </p>
                </div>
            </div>
        
        </div>
    </div>
    
    
    {{-- Filtros para el historial --}}

    <div class="filtro_padre">
        <form id="filterForm" class="filtro">
    
            <div class="filtro_container">
                <label for="filter-user" class="filtro_text">
                    Usuario
                </label>
                <select id="filter-user" name="user_id" class="filtro_select">
                    <option>Todos los usuarios</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                            {{ $user->name }}
                        </option>
                    @endforeach
                </select>
            </div>
    
            <div class="filtro_container">
                <label for="filter-action" class="filtro_text">
                    Acción
                </label>
                <select id="filter-action" name="action" class="filtro_select">
                    <option value="">Todas</option>
                    @foreach($actions as $action)
                        <option value="{{ $action }}" {{ request('action') == $action ? 'selected' : '' }}>
                            {{ ucfirst($action) }}
                        </option>
                    @endforeach
                </select>
            </div>
    
            <div class="filtro_container">
                <label for="filter-model" class="filtro_text">
                    Módulo
                </label>
                <select id="filter-model" name="model" class="filtro_select">
                    <option value="">Todos</option>
                    @foreach($models as $model)
                        <option value="{{ $model }}" {{ request('model') == $model ? 'selected' : '' }}>
                            {{ $model }}
                        </option>
                    @endforeach
                </select>
            </div>
    
            <div class="filtro_container">
                <label for="filter-date-from" class="filtro_text">
                    Desde
                </label>
                <input type="date" id="filter-date-from" name="date_from"
                    class="filtro_select"
                    value="{{ request('date_from') }}">
            </div>
    
            <div class="filtro_container">
                <label for="filter-date-to" class="filtro_text">
                    Hasta
                </label>
                <input type="date" id="filter-date-to" name="date_to"
                    class="filtro_select"
                    value="{{ request('date_to') }}">
            </div>
    
            <div class="btn_contendor">
                <button type="submit" class="btn_filtrar">
                    <label class="btn_text"> Filtrar </label>
                </button>
            </div>
    
        </form>
    </div>

    {{-- Tabla de registros --}}
    
    <div class="contenedor_tabla">
            <table class="tabla">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200">
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wide w-14">
                            Tipo
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wide">
                            Usuario
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wide">
                            Descripción
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wide w-32">
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
                                    <span class="inline-block px-2.5 py-0.5 rounded-md text-xs font-semibold bg-gray-100 text-gray-700 border border-gray-200">
                                        {{ $log->model }}
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
                                    {{ $log->created_at->format('H:i:s') }}
                                </div>
                            </td>
        
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-16 text-center text-gray-400">
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

@endsection