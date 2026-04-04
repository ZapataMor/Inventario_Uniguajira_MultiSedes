<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>REPORTES DE TODOS LOS BIENES</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; font-weight: bold; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h1 { margin: 0; color: #333; }
        .logo { margin-bottom: 10px; }
        .footer { text-align: center; font-size: 10px; margin-top: 30px; color: #666; }
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
        <h1>REPORTES DE TODOS LOS BIENES {{ $branding?->report_header_text ?? 'UNIGUAJIRA' }}</h1>
        <p>Fecha de generacion: {{ $date }}</p>
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
                    <td>{{ $good->tipo_bien }}</td>
                    <td>{{ $good->total_cantidad }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" style="text-align:center;">No hay bienes registrados.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <p>{{ $branding?->report_footer ?? 'Este documento es un reporte generado automáticamente.' }}</p>
    </div>
</body>
</html>

