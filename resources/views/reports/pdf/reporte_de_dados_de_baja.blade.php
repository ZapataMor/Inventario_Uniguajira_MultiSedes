<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>REPORTE DE DADOS DE BAJA</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; font-size: 11px; }
        th { background-color: #f2f2f2; font-weight: bold; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .header h1 { margin: 0; color: #333; font-size: 18px; }
        .header p { margin: 5px 0 0 0; font-size: 12px; }
        .logo { text-align: center; margin-bottom: 10px; }
        .summary { background-color: #f8f8f8; padding: 10px; margin-bottom: 20px; border: 1px solid #ddd; border-radius: 4px; }
        .summary p { margin: 4px 0; }
        .section-title {
            background-color: #d9534f;
            color: white;
            font-size: 14px;
            font-weight: bold;
            padding: 10px;
            margin: 20px 0 10px 0;
            border-radius: 4px;
        }
        .footer { text-align: center; font-size: 10px; margin-top: 30px; color: #666; border-top: 1px solid #ddd; padding-top: 10px; }
        .text-center { text-align: center; }
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
        <h1>REPORTE DE BIENES DADOS DE BAJA</h1>
        <p>Fecha de generacion: {{ $date }}</p>
    </div>

    <div class="summary">
        <p><strong>Total registros:</strong> {{ $totalRemoved }}</p>
        <p><strong>Total unidades dadas de baja:</strong> {{ $totalRemovedUnits }}</p>
    </div>

    <div class="section-title">BIENES DADOS DE BAJA - TIPO CANTIDAD</div>
    <table>
        <thead>
            <tr>
                <th>Bien</th>
                <th>Cantidad</th>
                <th>Motivo</th>
                <th>Grupo</th>
                <th>Inventario</th>
                <th>Usuario</th>
                <th>Fecha de baja</th>
            </tr>
        </thead>
        <tbody>
            @forelse($removedByQuantity as $item)
                <tr>
                    <td>{{ $item->bien }}</td>
                    <td class="text-center">{{ $item->cantidad }}</td>
                    <td>{{ $item->motivo }}</td>
                    <td>{{ $item->grupo }}</td>
                    <td>{{ $item->inventario }}</td>
                    <td>{{ $item->usuario ?? 'N/A' }}</td>
                    <td>{{ \Carbon\Carbon::parse($item->fecha_baja)->format('d/m/Y H:i') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center">No hay bienes dados de baja de tipo Cantidad.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="section-title">BIENES DADOS DE BAJA - TIPO SERIAL</div>
    <table>
        <thead>
            <tr>
                <th>Bien</th>
                <th>Serial</th>
                <th>Marca</th>
                <th>Modelo</th>
                <th>Estado</th>
                <th>Motivo</th>
                <th>Grupo</th>
                <th>Inventario</th>
                <th>Usuario</th>
                <th>Fecha de baja</th>
            </tr>
        </thead>
        <tbody>
            @forelse($removedBySerial as $item)
                <tr>
                    <td>{{ $item->bien }}</td>
                    <td>{{ $item->serial }}</td>
                    <td>{{ $item->marca }}</td>
                    <td>{{ $item->modelo }}</td>
                    <td>{{ $item->estado }}</td>
                    <td>{{ $item->motivo }}</td>
                    <td>{{ $item->grupo }}</td>
                    <td>{{ $item->inventario }}</td>
                    <td>{{ $item->usuario ?? 'N/A' }}</td>
                    <td>{{ \Carbon\Carbon::parse($item->fecha_baja)->format('d/m/Y H:i') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="text-center">No hay bienes dados de baja de tipo Serial.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <p>Este documento es un reporte generado automaticamente por el sistema de Inventario Uniguajira sede Maicao.</p>
    </div>
</body>
</html>

