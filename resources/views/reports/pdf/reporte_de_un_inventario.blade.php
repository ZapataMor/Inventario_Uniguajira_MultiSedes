<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>REPORTE DE INVENTARIO {{ $inventory->nombre }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; font-weight: bold; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .header { text-align: center; margin-bottom: 10px; }
        .header h1 { margin: 0; color: #333; }
        .logo { margin-bottom: 10px; text-align: center; }
        .info { text-align: center; margin-bottom: 20px; color: #555; }
        .footer { text-align: center; font-size: 10px; margin-top: 30px; color: #666; }
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
        <h1>REPORTE DE INVENTARIO: {{ $inventory->nombre }}</h1>
        <p>Fecha de generacion: {{ $date }}</p>
    </div>

    <div class="info">
        <p><strong>Grupo:</strong> {{ $inventory->grupo }}</p>
        <p><strong>Estado de conservacion del inventario:</strong> {{ $inventory->estado_conservacion }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Bien</th>
                <th>Tipo</th>
                <th>Cantidad</th>
            </tr>
        </thead>
        <tbody>
            @forelse($goods as $good)
                <tr>
                    <td>{{ $good->bien }}</td>
                    <td>{{ $good->tipo }}</td>
                    <td>{{ $good->cantidad }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" style="text-align:center;">No hay bienes registrados en este inventario.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <p>Este documento es un reporte generado automaticamente por el sistema de Inventario Uniguajira sede Maicao.</p>
    </div>
</body>
</html>

