<?php

namespace App\Http\Controllers;

use App\Models\Central\Tenant;
use App\Models\ReportFolder;
use App\Support\Tenancy\TenantConnectionManager;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class ReportFolderController extends Controller
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
        $isPortalReportsCatalog = ! tenant() && $request->user()?->isGlobalAdmin();
        $foldersBySede = collect();

        if ($isPortalReportsCatalog) {
            $foldersBySede = $this->getFoldersBySedeForPortal();
            $folders = $foldersBySede->flatMap(fn (array $sedeData) => $sedeData['folders'])->values();
        } else {
            $folders = ReportFolder::withCount('reports')
                ->orderByDesc('created_at')
                ->get();
        }

        if ($request->ajax()) {
            /** @var \Illuminate\View\View $view */
            $view = view('reports.folders.index', compact('folders', 'foldersBySede', 'isPortalReportsCatalog'));

            return $view->renderSections()['content'];
        }

        return view('reports.folders.index', compact('folders', 'foldersBySede', 'isPortalReportsCatalog'));
    }

    public function store(Request $request)
    {
        abort_if(! auth()->user()?->isAdministrator(), 403);

        $request->validate([
            'nombreCarpeta' => 'required|string|max:255|unique:report_folders,name',
        ]);

        $folder = ReportFolder::create([
            'name' => trim($request->nombreCarpeta),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Carpeta creada exitosamente.',
            'folder' => $folder,
        ]);
    }

    public function rename(Request $request)
    {
        abort_if(! auth()->user()?->isAdministrator(), 403);

        $request->validate([
            'folder_id' => 'required|exists:report_folders,id',
            'nombre' => 'required|string|max:255',
        ]);

        ReportFolder::findOrFail($request->folder_id)
            ->update(['name' => $request->nombre]);

        return response()->json([
            'success' => true,
            'message' => 'Carpeta renombrada exitosamente.',
        ]);
    }

    public function destroy(int $id)
    {
        abort_if(! auth()->user()?->isAdministrator(), 403);

        $folder = ReportFolder::findOrFail($id);

        if ($folder->reports()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'La carpeta contiene reportes.',
            ], 400);
        }

        $folder->delete();

        return response()->json([
            'success' => true,
            'message' => 'Carpeta eliminada exitosamente.',
        ]);
    }

    /**
     * Mostrar reportes dentro de una carpeta
     */
    public function show(int $folderId)
    {
        $folder = ReportFolder::findOrFail($folderId);
        $reports = $folder->reports()->orderByDesc('created_at')->get();

        return view('reports.folders.show', compact('folder', 'reports'));
    }

    /**
     * Consulta carpetas de reportes de cada sede activa para mostrarlas en portal.
     */
    private function getFoldersBySedeForPortal(): Collection
    {
        $tenants = Tenant::query()
            ->where('is_active', true)
            ->with('branding')
            ->orderBy('id')
            ->get();

        $tenantConnections = app(TenantConnectionManager::class);

        return $tenants->map(function (Tenant $tenant) use ($tenantConnections): array {
            return $tenantConnections->runForTenant($tenant, function (Tenant $tenant): array {
                try {
                    $folders = ReportFolder::on('tenant')
                        ->withCount('reports')
                        ->orderByDesc('created_at')
                        ->get();
                } catch (\Throwable $e) {
                    $folders = collect();
                }

                $sedeName = $this->resolveSedeName($tenant);

                return [
                    'tenant_id' => $tenant->id,
                    'tenant_slug' => $tenant->slug,
                    'sede_name' => $sedeName,
                    'dropdown_label' => "Reportes sede {$sedeName}",
                    'folders' => $folders,
                ];
            });
        });
    }

    private function resolveSedeName(Tenant $tenant): string
    {
        $rawName = trim((string) ($tenant->branding?->sede_name ?: $tenant->name ?: $tenant->slug));
        $normalized = preg_replace('/^sede\s+/iu', '', $rawName);

        return $normalized ?: ucfirst($tenant->slug);
    }
}
