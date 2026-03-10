<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use App\Services\Reports\SimplePdfService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class RecordController extends Controller
{
    public function __construct(private readonly SimplePdfService $pdfService)
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $logs = $this->filteredLogsQuery($request)
            ->paginate(50)
            ->withQueryString();

        $users = User::orderBy('name')->get();
        $actions = ActivityLog::select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action');
        $models = ActivityLog::select('model')
            ->distinct()
            ->whereNotNull('model')
            ->orderBy('model')
            ->pluck('model');

        if ($request->ajax()) {
            /** @var \Illuminate\View\View $view */
            $view = view('records.index', compact('logs', 'users', 'actions', 'models'));
            return $view->renderSections()['content'];
        }

        return view('records.index', compact('logs', 'users', 'actions', 'models'));
    }

    /**
     * Limpiar registros antiguos
     * DELETE /api/records/clean
     */
    public function clean(Request $request)
    {
        try {
            $days = $request->input('days', 30);

            $deleted = ActivityLog::where('created_at', '<', Carbon::now()->subDays($days))->delete();

            return response()->json([
                'success' => true,
                'type' => 'success',
                'message' => "Se eliminaron {$deleted} registros antiguos.",
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'type' => 'error',
                'message' => 'Ocurrió un error al limpiar los registros.',
            ], 500);
        }
    }

    /**
     * Exportar registros a PDF o CSV.
     */
    public function export(Request $request)
    {
        $logs = $this->filteredLogsQuery($request)->get();
        $format = strtolower((string) $request->input('format', 'pdf'));

        if ($format === 'csv') {
            return $this->exportCsv($logs);
        }

        $html = view('reports.pdf.reporte_de_historial', [
            'date' => now()->setTimezone('America/Bogota')->format('d/m/Y'),
            'logoDataUri' => $this->logoDataUri(),
            'logs' => $logs,
            'totalRecords' => $logs->count(),
            'weekCount' => ActivityLog::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'todayCount' => ActivityLog::whereDate('created_at', today())->count(),
            'activeUsersToday' => ActivityLog::distinct('user_id')
                ->whereDate('created_at', today())
                ->count('user_id'),
            'filters' => [
                'user' => $request->filled('user_id')
                    ? User::find($request->integer('user_id'))?->name
                    : null,
                'action' => $request->filled('action')
                    ? ucfirst((string) $request->input('action'))
                    : null,
                'model' => $request->filled('model')
                    ? (string) $request->input('model')
                    : null,
                'date_from' => $request->filled('date_from')
                    ? Carbon::parse((string) $request->input('date_from'))->format('d/m/Y')
                    : null,
                'date_to' => $request->filled('date_to')
                    ? Carbon::parse((string) $request->input('date_to'))->format('d/m/Y')
                    : null,
                'search' => $request->filled('search')
                    ? (string) $request->input('search')
                    : null,
            ],
        ])->render();

        $pdf = $this->pdfService->buildHtml($html, 'A4', 'landscape');
        $filename = $this->sanitizeFileName('reporte_historial_' . now()->format('Y-m-d_H-i-s')) . '.pdf';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    private function exportCsv(Collection $logs)
    {
        $filename = 'historial_' . date('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($logs) {
            $file = fopen('php://output', 'w');

            fputcsv($file, ['ID', 'Usuario', 'Acción', 'Módulo', 'Descripción', 'IP', 'Fecha/Hora']);

            foreach ($logs as $log) {
                fputcsv($file, [
                    $log->id,
                    $log->user?->name ?? 'Sistema',
                    ucfirst((string) $log->action),
                    $log->model_label ?? '-',
                    $log->description,
                    $log->ip_address ?? '-',
                    $log->created_at->format('d/m/Y H:i:s'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function filteredLogsQuery(Request $request): Builder
    {
        $query = ActivityLog::with('user')->orderBy('created_at', 'desc');

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        if ($request->filled('model')) {
            $query->where('model', $request->model);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $query->where('description', 'like', '%' . $request->search . '%');
        }

        return $query;
    }

    private function logoDataUri(): ?string
    {
        $path = public_path('assets/images/logoUniguajira.png');

        if (!file_exists($path)) {
            return null;
        }

        $imageData = file_get_contents($path);
        if ($imageData === false) {
            return null;
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $mime = $extension === 'jpg' || $extension === 'jpeg'
            ? 'image/jpeg'
            : ($extension === 'webp' ? 'image/webp' : 'image/png');

        return 'data:' . $mime . ';base64,' . base64_encode($imageData);
    }

    private function sanitizeFileName(string $name): string
    {
        $name = preg_replace('/[^a-zA-Z0-9\s\-_.]/', '', $name);
        $name = preg_replace('/\s+/', '_', (string) $name);
        $name = trim((string) $name, '_');

        return $name !== '' ? $name : 'reporte_' . now()->format('Y-m-d_H-i-s');
    }
}
