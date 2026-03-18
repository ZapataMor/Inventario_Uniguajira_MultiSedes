<?php

use App\Models\ActivityLog;
use App\Models\Asset;
use App\Models\AssetEquipment;
use App\Models\AssetInventory;
use App\Models\AssetQuantity;
use App\Models\Group;
use App\Models\Inventory;
use App\Models\Report;
use App\Models\ReportFolder;
use App\Models\Task;
use Illuminate\Routing\Route as IlluminateRoute;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

const WEB_ROUTE_DEFAULT_MAX_MS = 500;
const WEB_ROUTE_HEAVY_MAX_MS = 1500;

it('valida que las rutas web get relevantes respondan sin error y dentro del tiempo esperado', function () {
    ['admin' => $admin, 'params' => $params] = seedWebRoutesPerformanceData();

    $this->actingAs($admin);

    $routes = collect(Route::getRoutes())
        ->filter(function (IlluminateRoute $route): bool {
            if (! in_array('GET', $route->methods(), true)) {
                return false;
            }

            if (in_array('signed', $route->gatherMiddleware(), true)) {
                return false;
            }

            return ! shouldSkipWebRoutePerformanceUri($route->uri());
        })
        ->values();

    $tested = 0;

    foreach ($routes as $route) {
        $url = buildWebRoutePerformanceUrl($route->uri(), $params);

        if ($url === null) {
            continue;
        }

        [$response, $ms] = timedWebRouteGet($this, $url);
        $maxMs = maxMsForWebRoute($route->uri());

        $this->assertTrue(
            $response->isOk() || $response->isRedirection() || $response->getStatusCode() === 204,
            $route->uri() . ' devolvio ' . $response->getStatusCode()
        );

        $this->assertLessThan(
            $maxMs,
            $ms,
            $route->uri() . ' tardo ' . $ms . 'ms (max ' . $maxMs . 'ms)'
        );

        $tested++;
    }

    $this->assertGreaterThan(0, $tested, 'No se probaron rutas web.');
});

function seedWebRoutesPerformanceData(): array
{
    $admin = adminUser();

    Task::create([
        'name' => 'Tarea pendiente',
        'description' => 'Prueba de carga',
        'date' => now()->addDay()->toDateString(),
        'status' => 'pending',
        'user_id' => $admin->id,
    ]);

    Task::create([
        'name' => 'Tarea completada',
        'description' => 'Prueba de carga',
        'date' => now()->addDays(2)->toDateString(),
        'status' => 'completed',
        'user_id' => $admin->id,
    ]);

    $group = Group::create([
        'name' => 'Grupo rendimiento',
    ]);

    $inventory = Inventory::create([
        'name' => 'Inventario rendimiento',
        'responsible' => 'Usuario de prueba',
        'conservation_status' => 'good',
        'group_id' => $group->id,
    ]);

    $quantityAsset = Asset::create([
        'name' => 'Bien cantidad rendimiento',
        'type' => 'Cantidad',
    ]);

    $quantityRelation = AssetInventory::create([
        'asset_id' => $quantityAsset->id,
        'inventory_id' => $inventory->id,
    ]);

    AssetQuantity::create([
        'asset_inventory_id' => $quantityRelation->id,
        'quantity' => 3,
    ]);

    $serialAsset = Asset::create([
        'name' => 'Bien serial rendimiento',
        'type' => 'Serial',
    ]);

    $serialRelation = AssetInventory::create([
        'asset_id' => $serialAsset->id,
        'inventory_id' => $inventory->id,
    ]);

    AssetEquipment::create([
        'asset_inventory_id' => $serialRelation->id,
        'description' => 'Equipo de prueba',
        'brand' => 'Marca demo',
        'model' => 'Modelo demo',
        'serial' => 'SERIAL-PERF-001',
        'status' => 'activo',
        'color' => 'Negro',
        'technical_conditions' => 'Operativo',
        'entry_date' => now()->toDateString(),
    ]);

    $removedId = DB::table('assets_removed')->insertGetId([
        'name' => $quantityAsset->name,
        'type' => 'Cantidad',
        'image' => null,
        'quantity' => 1,
        'reason' => 'Baja de prueba',
        'asset_id' => $quantityAsset->id,
        'inventory_id' => $inventory->id,
        'user_id' => $admin->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $folder = ReportFolder::create([
        'name' => 'Carpeta rendimiento',
    ]);

    Report::create([
        'folder_id' => $folder->id,
        'name' => 'Reporte demo',
        'path' => 'reports/demo.pdf',
    ]);

    ActivityLog::create([
        'user_id' => $admin->id,
        'action' => 'create',
        'model' => 'Asset',
        'model_id' => $quantityAsset->id,
        'description' => 'Registro de prueba para historial',
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Pest',
        'old_values' => null,
        'new_values' => ['name' => $quantityAsset->name],
    ]);

    return [
        'admin' => $admin,
        'params' => [
            'groupId' => $group->id,
            'inventoryId' => $inventory->id,
            'assetId' => $serialAsset->id,
            'folderId' => $folder->id,
            'id' => $removedId,
            'path' => 'seeders/goods/img_67fb350ce3c8d.png',
            'token' => 'test-token',
        ],
    ];
}

function timedWebRouteGet(TestCase $testCase, string $url): array
{
    $testCase->get($url);

    $start = microtime(true);
    $response = $testCase->get($url);
    $ms = (int) round((microtime(true) - $start) * 1000);

    return [$response, $ms];
}

function buildWebRoutePerformanceUrl(string $uri, array $params): ?string
{
    if ($uri === '/' || $uri === '') {
        return '/';
    }

    $resolved = $uri;

    if (! preg_match_all('/\{([^}]+)\}/', $uri, $matches)) {
        return '/' . ltrim($resolved, '/');
    }

    foreach ($matches[1] as $rawParameter) {
        $optional = str_ends_with($rawParameter, '?');
        $parameter = rtrim($rawParameter, '?');

        if (! array_key_exists($parameter, $params)) {
            if ($optional) {
                $resolved = str_replace('{' . $rawParameter . '}', '', $resolved);
                continue;
            }

            return null;
        }

        $resolved = str_replace('{' . $rawParameter . '}', (string) $params[$parameter], $resolved);
    }

    $normalized = preg_replace('#//+#', '/', $resolved);

    return '/' . ltrim($normalized ?? $resolved, '/');
}

function shouldSkipWebRoutePerformanceUri(string $uri): bool
{
    $skipPrefixes = [
        'api/',
        'flux/',
        'livewire/',
        'storage/',
        'user/two-factor-',
    ];

    foreach ($skipPrefixes as $prefix) {
        if (str_starts_with($uri, $prefix)) {
            return true;
        }
    }

    return in_array($uri, [
        'register',
        'user/confirmed-password-status',
    ], true);
}

function maxMsForWebRoute(string $uri): int
{
    if (str_contains($uri, 'download') || str_contains($uri, 'export')) {
        return WEB_ROUTE_HEAVY_MAX_MS;
    }

    return WEB_ROUTE_DEFAULT_MAX_MS;
}
