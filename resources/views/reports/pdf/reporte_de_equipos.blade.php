<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>REPORTE DE TODOS LOS EQUIPOS</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 30px; page-break-inside: avoid; }
        th, td { border: 1px solid #ddd; padding: 5px; text-align: left; font-size: 9px; }
        th { background-color: #f2f2f2; font-weight: bold; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h1 { margin: 0; color: #333; font-size: 16px; margin-bottom: 10px; }
        .logo { margin-bottom: 10px; }
        .footer { text-align: center; font-size: 10px; margin-top: 30px; color: #666; }
        .section-title {
            background-color: #e8e8e8;
            color: #333;
            font-size: 14px;
            font-weight: bold;
            padding: 10px;
            margin: 20px 0 10px 0;
            border-left: 4px solid #4CAF50;
            page-break-after: avoid;
        }
        .goods-count { font-size: 12px; color: #666; font-weight: normal; margin-left: 10px; }
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
        <h1>REPORTE DE TODOS LOS EQUIPOS {{ $branding?->report_header_text ?? 'UNIGUAJIRA' }}</h1>
        <p>Fecha de generacion: {{ $date }}</p>
    </div>

    @forelse($groupedGoods as $group)
        <div>
            <div class="section-title">
                {{ $group['bien'] }}
                <span class="goods-count">({{ $group['items']->count() }} equipo{{ $group['items']->count() === 1 ? '' : 's' }})</span>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Descripcion</th>
                        <th>Marca</th>
                        <th>Modelo</th>
                        <th>Serial</th>
                        <th>Ubicacion</th>
                        <th>Estado</th>
                        <th>Condicion</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($group['items'] as $item)
                        <tr>
                            <td>{{ $item->descripcion }}</td>
                            <td>{{ $item->marca }}</td>
                            <td>{{ $item->modelo }}</td>
                            <td>{{ $item->serial }}</td>
                            <td>{{ $item->nombre_inventario ?? 'No especificada' }}</td>
                            <td>{{ $item->estado }}</td>
                            <td>{{ $item->condiciones_tecnicas }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @empty
        <p style="text-align:center;">No hay equipos serializados registrados.</p>
    @endforelse

    <div class="footer">
        <p>{{ $branding?->report_footer ?? 'Este documento es un reporte generado automáticamente.' }}</p>
    </div>
</body>
</html>

