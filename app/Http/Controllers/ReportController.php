<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;

use App\Models\Report;
use App\Models\ReportFolder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Vista principal (carpetas)
     */
    public function index(Request $request)
    {
        $folders = ReportFolder::withCount('reports')->get();

        if ($request->ajax()) {
            return view('reports.index', compact('folders'))->render();
        }

        return view('reports.index', compact('folders'));
    }

    /**
     * Crear reporte y generar PDF
     */
    // public function store(Request $request)
    // {
    //     $request->validate([
    //         'folder_id'     => 'required|exists:report_folders,id',
    //         'nombreReporte' => 'required|string|max:255',
    //         'tipoReporte'   => 'required|string'
    //     ]);

    //     $outputPath = null;

    //     try {
    //         // Delegar la generación a services (adapta nombres de clases si tuvieras otros)
    //         $tipo = $request->input('tipoReporte');

    //         $outputPath = match ($tipo) {
    //             'inventario'     => app()->make(\App\Services\Reports\InventoryReport::class)->generate($request),
    //             'grupo'          => app()->make(\App\Services\Reports\GroupReport::class)->generate($request),
    //             'allInventories' => app()->make(\App\Services\Reports\AllInventoriesReport::class)->generate(),
    //             'goods'          => app()->make(\App\Services\Reports\GoodsReport::class)->generate(),
    //             'serial'         => app()->make(\App\Services\Reports\SerialGoodsReport::class)->generate(),
    //             default          => null,
    //         };

    //         if (!$outputPath) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Tipo de reporte no soportado.'
    //             ], 400);
    //         }

    //         // Guardar registro en la BD (ajusta nombres de campos si tu tabla usa otros)
    //         $report = Report::create([
    //             'name'      => $request->input('nombreReporte'),
    //             'folder_id' => $request->input('folder_id'),
    //             'path'      => $outputPath,
    //         ]);

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Reporte generado exitosamente.',
    //             'report'  => $report
    //         ]);
    //     } catch (\Throwable $e) {
    //         Log::error('Error generando reporte: ' . $e->getMessage(), [
    //             'payload' => $request->all()
    //         ]);

    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Error interno al generar el reporte.'
    //         ], 500);
    //     }
    // }

    /**
     * Renombrar reporte
     */
    public function rename(Request $request)
    {
        $request->validate([
            'report_id' => 'required|exists:reports,id',
            'nombre'    => 'required|string|max:255'
        ]);

        $report = Report::findOrFail($request->input('report_id'));
        $report->update(['name' => $request->input('nombre')]);

        return response()->json([
            'success' => true,
            'message' => 'Reporte actualizado exitosamente.'
        ]);
    }

    /**
     * Eliminar reporte (archivo + BD)
     */
    public function destroy(int $id)
    {
        $report = Report::findOrFail($id);

        try {
            // Intentar borrar desde Storage (si fue guardado ahí)
            if ($report->path && Storage::exists($report->path)) {
                Storage::delete($report->path);
            } else {
                // Intentar con rutas relativas/absolutas públicas o del filesystem
                $possible = [
                    public_path($report->path ?? ''),
                    storage_path('app/' . ($report->path ?? '')),
                    base_path($report->path ?? ''),
                    $report->path ?? ''
                ];

                foreach ($possible as $p) {
                    if ($p && file_exists($p)) {
                        @unlink($p);
                        break;
                    }
                }
            }

            $report->delete();

            return response()->json([
                'success' => true,
                'message' => 'Reporte eliminado exitosamente.'
            ]);
        } catch (\Throwable $e) {
            Log::error('Error eliminando reporte: ' . $e->getMessage(), ['report_id' => $id]);

            return response()->json([
                'success' => false,
                'message' => 'No se pudo eliminar el reporte.'
            ], 500);
        }
    }

    /**
     * Descargar PDF de un reporte.
     * Espera report_id en payload (POST o GET).
     */
    public function download(Request $request)
    {
        $request->validate([
            'report_id' => 'required|exists:reports,id'
        ]);

        $report = Report::findOrFail($request->input('report_id'));
        $filePath = $report->path ?? null;

        if (!$filePath) {
            return response()->json([
                'success' => false,
                'message' => 'Ruta de reporte no disponible'
            ], 404);
        }

        // 1) Si está en Storage
        if (Storage::exists($filePath)) {
            return Storage::download($filePath, $this->sanitizeFileName($report->name) . '.pdf');
        }

        // 2) Si es una ruta pública relativa a public/
        $publicPath = public_path($filePath);
        if (file_exists($publicPath)) {
            return response()->download($publicPath, $this->sanitizeFileName($report->name) . '.pdf', [
                'Content-Type' => 'application/pdf'
            ]);
        }

        // 3) Si es ruta absoluta o relativa en el FS
        if (file_exists($filePath)) {
            return response()->download($filePath, $this->sanitizeFileName($report->name) . '.pdf', [
                'Content-Type' => 'application/pdf'
            ]);
        }

        // No encontrado
        return response()->json([
            'success' => false,
            'message' => 'Archivo no encontrado en el servidor'
        ], 404);
    }

    /**
     * Sanitizar nombre archivo
     */
    private function sanitizeFileName(string $name): string
    {
        $name = preg_replace('/[^a-zA-Z0-9\s\-_.]/', '', $name);
        $name = preg_replace('/\s+/', '_', $name);
        $name = trim($name, '_');

        return $name !== '' ? $name : 'reporte_' . now()->format('Y-m-d_H-i-s');
    }
}
