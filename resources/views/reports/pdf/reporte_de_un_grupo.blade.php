<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>REPORTE DE GRUPO - {{ $group->nombre }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; font-weight: bold; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .header { text-align: center; margin-bottom: 10px; }
        .header h1 { margin: 0; color: #333; }
        .footer { text-align: center; font-size: 10px; margin-top: 30px; color: #666; }
        .inventory-section { margin-top: 30px; margin-bottom: 40px; page-break-inside: avoid; }
        .inventory-title { background-color: #eaeaea; padding: 10px; margin-bottom: 15px; border-left: 5px solid #4a90e2; }
        .inventory-status { margin: 10px 0; font-style: italic; color: #555; }
        .logo { text-align: center; margin-bottom: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">
            @if($logoDataUri)
                <img src="{{ $logoDataUri }}" width="500" alt="{{ $branding?->sede_name ?? 'Logo' }}">
            @else
                <div style="width:300px;height:100px;border:2px solid #333;display:flex;align-items:center;justify-content:center;margin:0 auto;">
                    <span style="color:#333;font-weight:bold;">{{ $branding?->report_header_text ?? 'UNIGUAJIRA' }}</span>
                </div>
            @endif
        </div>
        <h1>REPORTE DE GRUPO: {{ $group->nombre }}</h1>
        <p>Fecha de generacion: {{ $date }}</p>
    </div>

    @forelse($inventories as $inventory)
        <div class="inventory-section">
            <h2 class="inventory-title">Inventario: {{ $inventory->nombre }}</h2>
            <div class="inventory-status">
                <p><strong>Estado de conservacion:</strong> {{ $inventory->estado_conservacion }}</p>
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
                    @forelse($inventory->goods as $good)
                        <tr>
                            <td>{{ $good->bien }}</td>
                            <td>{{ $good->tipo }}</td>
                            <td>{{ $good->cantidad }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" style="text-align:center;">No hay bienes registrados en este inventario</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @empty
        <p style="text-align:center;">No hay inventarios registrados en este grupo.</p>
    @endforelse

    <div class="footer">
        <p>{{ $branding?->report_footer ?? 'Este documento es un reporte generado automáticamente.' }}</p>
    </div>
</body>
</html>

