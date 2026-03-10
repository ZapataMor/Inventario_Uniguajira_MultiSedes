<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>REPORTE DE HISTORIAL</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; margin: 20px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; vertical-align: top; font-size: 10px; }
        th { background-color: #f2f2f2; font-weight: bold; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .header h1 { margin: 0; color: #333; font-size: 18px; }
        .header p { margin: 5px 0 0 0; font-size: 12px; }
        .logo { text-align: center; margin-bottom: 10px; }
        .summary { background-color: #f8f8f8; padding: 10px; margin-bottom: 20px; border: 1px solid #ddd; border-radius: 4px; }
        .summary-table th { background-color: #e0e0e0; }
        .filters { margin-top: 10px; font-size: 11px; }
        .filters span { display: inline-block; margin-right: 12px; margin-bottom: 6px; }
        .footer { text-align: center; font-size: 10px; margin-top: 30px; color: #666; border-top: 1px solid #ddd; padding-top: 10px; }
        .text-center { text-align: center; }
        .muted { color: #666; }
        .username { font-size: 9px; color: #666; margin-top: 3px; }
        .description { font-weight: bold; margin-bottom: 4px; }
        .changes { font-size: 9px; color: #444; }
        .change-item { display: block; margin-top: 2px; }
        .module-badge { font-size: 10px; font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">
            @if($logoDataUri)
                <img src="{{ $logoDataUri }}" width="500" alt="Logo Uniguajira">
            @else
                <div style="width:300px;height:100px;border:2px solid #333;display:flex;align-items:center;justify-content:center;margin:0 auto;">
                    <span style="color:#333;font-weight:bold;">UNIGUAJIRA MAICAO</span>
                </div>
            @endif
        </div>
        <h1>REPORTE GENERAL DE HISTORIAL</h1>
        <p>Fecha de generacion: {{ $date }}</p>
    </div>

    <div class="summary">
        <table class="summary-table">
            <tr>
                <th>Total registros</th>
                <th>Esta semana</th>
                <th>Acciones hoy</th>
                <th>Usuarios activos hoy</th>
            </tr>
            <tr>
                <td class="text-center">{{ $totalRecords }}</td>
                <td class="text-center">{{ $weekCount }}</td>
                <td class="text-center">{{ $todayCount }}</td>
                <td class="text-center">{{ $activeUsersToday }}</td>
            </tr>
        </table>

        @if(collect($filters)->filter()->isNotEmpty())
            <div class="filters">
                @if($filters['user'])
                    <span><strong>Usuario:</strong> {{ $filters['user'] }}</span>
                @endif
                @if($filters['action'])
                    <span><strong>Accion:</strong> {{ $filters['action'] }}</span>
                @endif
                @if($filters['model'])
                    <span><strong>Modulo:</strong> {{ $filters['model'] }}</span>
                @endif
                @if($filters['date_from'])
                    <span><strong>Desde:</strong> {{ $filters['date_from'] }}</span>
                @endif
                @if($filters['date_to'])
                    <span><strong>Hasta:</strong> {{ $filters['date_to'] }}</span>
                @endif
                @if($filters['search'])
                    <span><strong>Busqueda:</strong> {{ $filters['search'] }}</span>
                @endif
            </div>
        @endif
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 6%;">N°</th>
                <th style="width: 10%;">Tipo</th>
                <th style="width: 18%;">Usuario</th>
                <th style="width: 38%;">Descripcion</th>
                <th style="width: 12%;">Modulo</th>
                <th style="width: 16%;">Fecha / Hora</th>
            </tr>
        </thead>
        <tbody>
            @forelse($logs as $log)
                <tr>
                    <td class="text-center">{{ $log->id }}</td>
                    <td>{{ ucfirst($log->action) }}</td>
                    <td>
                        <div>{{ $log->user?->name ?? 'Sistema' }}</div>
                        <div class="username">{{ $log->user?->username ?? 'system' }}</div>
                    </td>
                    <td>
                        <div class="description">{{ $log->description }}</div>
                        @if($log->old_values && $log->action === 'update')
                            <div class="changes">
                                @foreach(array_keys($log->old_values) as $key)
                                    @if(isset($log->new_values[$key]) && $log->old_values[$key] != $log->new_values[$key])
                                        <span class="change-item">
                                            {{ $key }}: {{ \Illuminate\Support\Str::limit((string) $log->old_values[$key], 24) }}
                                            ->
                                            {{ \Illuminate\Support\Str::limit((string) $log->new_values[$key], 24) }}
                                        </span>
                                    @endif
                                @endforeach
                            </div>
                        @endif
                    </td>
                    <td>
                        <span class="module-badge">{{ $log->model_label ?? '-' }}</span>
                    </td>
                    <td>
                        <div>{{ $log->created_at->format('d/m/Y') }}</div>
                        <div class="muted">{{ $log->created_at->format('g:i A') }}</div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center">No hay registros de actividad para incluir en el reporte.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <p>Este documento es un reporte generado automaticamente por el sistema de Inventario Uniguajira sede Maicao.</p>
    </div>
</body>
</html>
