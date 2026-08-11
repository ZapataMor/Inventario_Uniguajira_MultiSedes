<?php

namespace App\Http\Controllers;

use App\Helpers\ActivityLogger;
use App\Models\Central\Tenant;
use App\Models\InventorySchedule;
use App\Models\InventoryScheduleEntry;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Formulario publico de la "Programacion de inventarios".
 *
 * Se accede sin iniciar sesion, escaneando el QR o abriendo el enlace.
 * El slug de la sede viaja en la URL para poder activar la conexion
 * tenant correcta sin depender de la sesion del visitante.
 *
 * Alcance deliberadamente minimo: solo permite crear una labor sobre
 * una programacion abierta. No expone ni modifica bienes, inventarios
 * ni usuarios.
 *
 * El formulario es de un solo uso: al enviarlo, la programacion queda
 * cerrada y el QR deja de admitir registros. Cada labor nueva necesita
 * su propia programacion con su propio QR.
 */
class PublicScheduleController extends Controller
{
    /**
     * GET /programacion/{tenantSlug}/{code}
     */
    public function show(string $tenantSlug, string $code)
    {
        [$tenant, $schedule] = $this->resolve($tenantSlug, $code);

        return view('schedules.public.form', [
            'tenant' => $tenant,
            'branding' => $tenant->branding,
            'schedule' => $schedule,
            'entry' => $schedule->entry,
            'submitted' => (bool) session('schedule_submitted'),
        ]);
    }

    /**
     * POST /programacion/{tenantSlug}/{code}
     */
    public function store(Request $request, string $tenantSlug, string $code): RedirectResponse
    {
        [, $schedule] = $this->resolve($tenantSlug, $code);

        // Un QR solo se diligencia una vez: el segundo envio se rechaza
        // aunque alguien conserve el enlace o reenvie el formulario.
        if ($schedule->isCompleted() || ! $schedule->is_open) {
            return back()->withErrors([
                'work_name' => 'Esta programación ya no admite nuevos registros.',
            ])->withInput();
        }

        $data = $request->validate([
            'work_name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'responsible_name' => ['required', 'string', 'max:255'],
            'started_at' => ['required', 'date'],
            'finished_at' => ['required', 'date', 'after_or_equal:started_at'],
        ], [
            'work_name.required' => 'Escribe el nombre del trabajo realizado.',
            'responsible_name.required' => 'Escribe el nombre del responsable.',
            'started_at.required' => 'Indica la fecha y hora en que iniciaste.',
            'finished_at.required' => 'Indica la fecha y hora en que terminaste.',
            'finished_at.after_or_equal' => 'La fecha de finalización no puede ser anterior a la de inicio.',
        ]);

        InventoryScheduleEntry::create([
            'inventory_schedule_id' => $schedule->id,
            'work_name' => $data['work_name'],
            'description' => $data['description'] ?? null,
            'responsible_name' => $data['responsible_name'],
            'started_at' => $data['started_at'],
            'finished_at' => $data['finished_at'],
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 500),
        ]);

        // El QR queda consumido: la programacion no vuelve a recibir labores.
        $schedule->forceFill(['is_open' => false])->save();

        ActivityLogger::custom(
            'create',
            "Registro externo en la programación: {$schedule->title} ({$data['work_name']})",
            [
                'model' => 'InventorySchedule',
                'model_id' => $schedule->id,
            ]
        );

        return back()->with('schedule_submitted', true);
    }

    /**
     * Activa la sede indicada en la URL y devuelve la programacion.
     *
     * @return array{0: Tenant, 1: InventorySchedule}
     */
    private function resolve(string $tenantSlug, string $code): array
    {
        $tenant = Tenant::where('slug', $tenantSlug)
            ->where('is_active', true)
            ->first();

        abort_if(! $tenant, 404);

        app(TenantContext::class)->set($tenant);

        $schedule = InventorySchedule::where('code', $code)->first();

        abort_if(! $schedule, 404);

        return [$tenant, $schedule];
    }
}
