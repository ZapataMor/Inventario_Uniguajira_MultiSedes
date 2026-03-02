<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>REPORTE GENERAL DE INVENTARIOS</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; margin: 20px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; font-size: 11px; }
        th { background-color: #f2f2f2; font-weight: bold; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .header h1 { margin: 0; color: #333; font-size: 18px; }
        .header p { margin: 5px 0 0 0; font-size: 12px; }
        .footer { text-align: center; font-size: 10px; margin-top: 30px; color: #666; border-top: 1px solid #ddd; padding-top: 10px; }
        .group-section { margin-top: 40px; margin-bottom: 20px; }
        .group-title { background-color: #4a90e2; color: white; padding: 10px; margin-bottom: 15px; font-size: 16px; border-radius: 4px; }
        .inventory-section { margin-top: 20px; margin-bottom: 30px; page-break-inside: avoid; }
        .inventory-title { background-color: #eaeaea; padding: 8px; margin-bottom: 10px; border-left: 5px solid #4a90e2; font-size: 14px; }
        .inventory-status { margin: 5px 0 10px 0; font-style: italic; color: #555; font-size: 11px; }
        .page-break { page-break-after: always; }
        .summary { background-color: #f8f8f8; padding: 10px; margin-bottom: 30px; border: 1px solid #ddd; border-radius: 4px; }
        .summary h2 { font-size: 14px; margin-top: 0; margin-bottom: 10px; color: #333; }
        .summary-table { width: 100%; border-collapse: collapse; }
        .summary-table th { background-color: #e0e0e0; }
        .no-items { text-align: center; padding: 20px; color: #777; font-style: italic; }
        .text-center { text-align: center; }
        .logo { text-align: center; margin-bottom: 10px; }
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
        <h1>REPORTE GENERAL DE INVENTARIOS</h1>
        <p>Fecha de generacion: {{ $date }}</p>
    </div>

    <div class="summary">
        <h2>Resumen General</h2>
        <table class="summary-table">
            <tr>
                <th>Total Grupos</th>
                <th>Total Inventarios</th>
                <th>Total Bienes</th>
            </tr>
            <tr>
                <td class="text-center">{{ $totalGroups }}</td>
                <td class="text-center">{{ $totalInventories }}</td>
                <td class="text-center">{{ $totalGoods }}</td>
            </tr>
        </table>
    </div>

    @foreach($groups as $group)
        <div class="group-section">
            <h2 class="group-title">GRUPO: {{ $group->nombre }}</h2>

            @if($group->inventories->isEmpty())
                <div class="no-items">
                    <p>No hay inventarios registrados en este grupo</p>
                </div>
            @else
                @foreach($group->inventories as $inventory)
                    <div class="inventory-section">
                        <h3 class="inventory-title">Inventario: {{ $inventory->nombre }}</h3>
                        <div class="inventory-status">
                            <p><strong>Estado de conservacion:</strong> {{ $inventory->estado_conservacion }}</p>
                            <p><strong>Ultima modificacion:</strong> {{ $inventory->fecha_modificacion }}</p>
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
                                        <td class="text-center">{{ $good->cantidad }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center">No hay bienes registrados en este inventario</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                @endforeach
            @endif
        </div>
    @endforeach

    <div class="footer">
        <p>Este documento es un reporte generado automaticamente por el sistema de Inventario Uniguajira sede Maicao.</p>
    </div>
</body>
</html>

