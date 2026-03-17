<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Asset;
use App\Models\AssetInventory;
use Illuminate\Support\Facades\Storage;
use App\Helpers\ActivityLogger;

class GoodsController extends Controller
{
    /**
     * Muestra un listado del recurso.
     * Se obtienen los datos desde una vista de base de datos para mejorar la eficiencia de las consultas.
     * Se diferencia entre una petición AJAX y una carga de página normal para poder renderizar
     * secciones específicas (SPA) o la vista completa.
     */
    public function index(Request $request)
    {
        // Se consulta la vista SQL que resume la información de los bienes para evitar lógica compleja en el controlador.
        $dataGoods = DB::table('assets_summary_view')->get();

        if ($request->ajax()) {
            // Si la petición es AJAX, se renderiza únicamente la sección 'content' de la vista para una actualización parcial de la página.
            /** @var \Illuminate\View\View $view */
            $view = view('goods.index', compact('dataGoods'));
            return $view->renderSections()['content'];
        }

        // Si es una petición HTTP estándar, se devuelve la vista completa con el layout.
        return view('goods.index', compact('dataGoods'));
    }

    /**
     * GET /api/goods/get/json
     * Proporciona un listado JSON de todos los bienes con su ID, nombre y tipo para ser consumido por el frontend
     * en componentes dinámicos como selectores o tablas.
     */
    public function getJson()
    {
        // Se obtienen únicamente las columnas necesarias de la tabla de bienes para minimizar la transferencia de datos.
        $goods = Asset::select('id', 'name as bien', 'type as tipo')->get();

        return response()->json($goods);
    }

    /**
     * Almacena un recurso recién creado en la base de datos.
     * Esta acción está restringida únicamente a usuarios con rol de administrador.
     * Se valida la entrada, se procesa una imagen opcional y se registra la acción en el log de actividad.
     */
    public function store(Request $request)
    {
        // Se verifica que el usuario autenticado tenga permisos de administrador; de lo contrario, se aborta la petición.
        abort_if(auth()->user()->role !== 'administrador', 403);

        $request->validate([
            'nombre' => 'required|string|max:255|unique:assets,name',
            'tipo'   => 'required|integer|in:1,2',
            'imagen' => 'nullable|image|max:2048' // 2 MB
        ]);

        // Se guarda la imagen en el disco 'public' si se ha proporcionado una en la petición.
        $path = null;
        if ($request->hasFile('imagen')) {
            $path = $request->file('imagen')->store('assets/goods', 'public');
        }

        $asset = Asset::create([
            'name'  => $request->nombre,
            'type'  => $request->tipo == 1 ? 'Cantidad' : 'Serial',
            'image' => $path
        ]);

        // Se registra la creación del bien para mantener una traza de auditoría.
        ActivityLogger::created(Asset::class, $asset->id, $asset->name);

        return response()->json([
            'success' => true,
            'message' => 'Bien creado exitosamente.',
            'assetId' => $asset->id
        ]);
    }

    /**
     * Actualiza un recurso específico en la base de datos.
     * Requiere permisos de administrador. Se valida la existencia del recurso, los datos de entrada,
     * se gestiona la sustitución de imágenes y se registra el cambio.
     */
    public function update(Request $request)
    {
        // Se asegura de que el usuario sea administrador antes de proceder con la actualización.
        abort_if(auth()->user()->role !== 'administrador', 403);

        $asset = Asset::findOrFail($request->id);

        $request->validate([
            'id'     => 'required|integer|exists:assets,id',
            'nombre' => 'required|string|max:255|unique:assets,name,' . $asset->id,
            'imagen' => 'nullable|image|max:2048'
        ]);

        // Se capturan los valores actuales antes de la modificación para poder registrar qué cambió exactamente.
        $oldValues = [
            'name' => $asset->name,
            'image' => $asset->image,
        ];

        // Si se ha subido una nueva imagen, se reemplaza la anterior.
        if ($request->hasFile('imagen')) {
            // Se elimina el archivo de imagen antiguo del sistema de archivos si existe para liberar espacio.
            if ($asset->image && Storage::disk('public')->exists($asset->image)) {
                Storage::disk('public')->delete($asset->image);
            }

            // Se guarda la nueva imagen en el disco.
            $asset->image = $request->file('imagen')->store('assets/goods', 'public');
        }

        // Se actualiza el nombre del bien.
        $asset->name = $request->nombre;

        $asset->save();

        // Se registra la actividad de actualización, incluyendo los valores antiguos y nuevos para una auditoría detallada.
        ActivityLogger::updated(
            Asset::class,
            $asset->id,
            $asset->name,
            $oldValues,
            [
                'name' => $asset->name,
                'image' => $asset->image,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Bien actualizado correctamente.'
        ]);
    }

    /**
     * Elimina un recurso específico de la base de datos.
     * Función exclusiva para administradores. Antes de eliminar, se verifica que el bien no tenga existencias
     * (cantidades o equipos) asociadas para mantener la integridad de los datos. También se elimina su imagen asociada.
     */
    public function destroy(string $id)
    {
        // Se valida que el usuario tenga el rol adecuado para realizar esta operación.
        abort_if(auth()->user()->role !== 'administrador', 403);

        $asset = Asset::find($id);

        if (!$asset) {
            return response()->json([
                'success' => false,
                'message' => 'Bien no encontrado.'
            ], 404);
        }

        // Se calcula la cantidad total de este bien en el inventario (suma de cantidades y equipos) para prevenir la eliminación si está en uso.
        $total = AssetInventory::where('asset_id', $id)
            ->leftJoin('asset_quantities', 'asset_inventory.id', '=', 'asset_quantities.asset_inventory_id')
            ->leftJoin('asset_equipments', 'asset_inventory.id', '=', 'asset_equipments.asset_inventory_id')
            ->selectRaw("
                COALESCE(SUM(asset_quantities.quantity), 0) +
                COALESCE(COUNT(asset_equipments.id), 0) AS total
            ")
            ->value('total');

        if ($total > 0) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar el bien porque su cantidad es mayor a 0.'
            ], 400);
        }

        $assetName = $asset->name; // Se guarda el nombre para el log de actividad, ya que el objeto se eliminará.

        // Se elimina la imagen del bien del disco duro para no dejar archivos huérfanos.
        if ($asset->image && Storage::disk('public')->exists($asset->image)) {
            Storage::disk('public')->delete($asset->image);
        }

        $asset->delete();

        // Se registra la eliminación en el log de actividad.
        ActivityLogger::deleted(Asset::class, $id, $assetName);

        return response()->json([
            'success' => true,
            'message' => 'Bien eliminado correctamente.'
        ]);
    }

    /**
     * Crea múltiples bienes a partir de una carga por archivo Excel (POST /api/goods/batchCreate).
     * Recibe un array de bienes con sus datos e imágenes opcionales.
     * Su propósito es permitir la creación masiva. Se procesa cada ítem individualmente,
     * manejando errores por fila y registrando al final un resumen de la operación.
     */
    public function batchCreate(Request $request)
    {
        // Solo los administradores pueden realizar cargas masivas.
        abort_if(auth()->user()->role !== 'administrador', 403);

        try {
            $goods = $request->input('goods', []);

            if (empty($goods)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se recibieron datos válidos.'
                ], 400);
            }

            $created = 0;
            $errors = [];
            $createdAssets = []; // Se almacenan los nombres de los bienes creados para el resumen del log.

            foreach ($goods as $index => $good) {
                try {
                    // Se valida que los datos de cada bien sean correctos antes de intentar crearlos.
                    $validator = validator($good, [
                        'nombre' => 'required|string|unique:assets,name',
                        'tipo' => 'required|in:1,2'
                    ]);

                    if ($validator->fails()) {
                        $errors[] = "Fila {$index}: " . $validator->errors()->first();
                        continue;
                    }

                    // Se procesa la imagen si existe para el índice actual.
                    $imagePath = null;
                    $imageKey = "goods_{$index}_imagen";

                    if ($request->hasFile($imageKey)) {
                        $image = $request->file($imageKey);

                        $validator = validator(['imagen' => $image], [
                            'imagen' => 'image|mimes:jpeg,png,jpg,gif,webp|max:2048'
                        ]);

                        if ($validator->fails()) {
                            $errors[] = "Fila {$index}: " . $validator->errors()->first();
                            continue;
                        }

                        $imagePath = $image->store('assets/goods', 'public');
                    }

                    // Se crea el registro del bien en la base de datos.
                    $asset = Asset::create([
                        'name' => $good['nombre'],
                        'type' => (int)$good['tipo'] === 1 ? 'Cantidad' : 'Serial',
                        'image' => $imagePath
                    ]);

                    $createdAssets[] = $asset->name;
                    $created++;

                } catch (\Exception $e) {
                    // Se captura cualquier excepción inesperada durante la creación de un bien para que el proceso continúe con los demás.
                    $errors[] = "Fila {$index}: {$e->getMessage()}";
                }
            }

            // Si se creó al menos un bien, se registra una entrada de actividad personalizada para la operación masiva.
            if ($created > 0) {
                ActivityLogger::custom(
                    'batch_create',
                    "Creó {$created} bien(es) mediante carga masiva: " . implode(', ', array_slice($createdAssets, 0, 5)) . ($created > 5 ? '...' : ''),
                    [
                        'model' => 'Asset',
                        'count' => $created,
                        'assets' => $createdAssets,
                    ]
                );
            }

            $message = "Se crearon {$created} bien(es) exitosamente.";
            if (!empty($errors)) {
                $message .= " " . count($errors) . " error(es).";
            }

            return response()->json([
                'success' => $created > 0,
                'message' => $message,
                'created' => $created,
                'errors' => $errors
            ]);

        } catch (\Exception $e) {
            // Error general del proceso de carga masiva, como problemas con la petición misma.
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Descarga una plantilla Excel (GET /api/goods/download-template) con el formato correcto
     * para la importación de bienes. Esto facilita al usuario entender la estructura de datos requerida.
     */
    public function downloadTemplate()
    {
        $filePath = storage_path('app/templates/Plantilla Crear Bienes.xlsx');

        if (!file_exists($filePath)) {
            return response()->json([
                'success' => false,
                'message' => 'El archivo de plantilla no se encuentra disponible.'
            ], 404);
        }

        return response()->download($filePath, 'Plantilla Crear Bienes.xlsx');
    }

    /**
     * Muestra la vista para la subida de bienes mediante archivo Excel (GET /api/goods/excel-upload/view).
     * Al igual que en el índice, soporta peticiones AJAX para cargar solo el contenido del modal
     * y peticiones normales para cargar una página completa.
     */
    public function excelUploadView(Request $request)
    {
        if ($request->ajax()) {
            // Renderiza solo el contenido interno del modal para una experiencia de aplicación de una sola página (SPA).
            return view('components.modal.goods.excel-upload')
                ->renderSections()['content'];
        }

        return view('goods.excel-upload');
    }
}