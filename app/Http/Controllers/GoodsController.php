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
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Consultar la vista SQL
        $dataGoods = DB::table('assets_summary_view')->get();

        if ($request->ajax()) {
            // si es una carga AJAX, solo renderiza el contenido interno
            return view('goods.index', compact('dataGoods'))
                ->renderSections()['content'];
        }

        // si es carga normal (primera vez), usa el layout completo
        return view('goods.index', compact('dataGoods'));
    }

    /**
     * GET /api/goods/get/json
     * Devuelve todos los bienes con id, nombre y tipo (igual que la versión PHP)
     */
    public function getJson()
    {
        // Obtener todos los bienes desde la tabla `assets`
        $goods = Asset::select('id', 'name as bien', 'type as tipo')->get();

        return response()->json($goods);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        abort_if(auth()->user()->role !== 'administrador', 403);

        $request->validate([
            'nombre' => 'required|string|max:255|unique:assets,name',
            'tipo'   => 'required|integer|in:1,2',
            'imagen' => 'nullable|image|max:2048' // 2 MB
        ]);

        // Guardar imagen si existe
        $path = null;
        if ($request->hasFile('imagen')) {
            $path = $request->file('imagen')->store('assets/goods', 'public');
        }

        $asset = Asset::create([
            'name'  => $request->nombre,
            'type'  => $request->tipo == 1 ? 'Cantidad' : 'Serial',
            'image' => $path
        ]);

        // ✅ Registrar actividad
        ActivityLogger::created(Asset::class, $asset->id, $asset->name);

        return response()->json([
            'success' => true,
            'message' => 'Bien creado exitosamente.',
            'assetId' => $asset->id
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        abort_if(auth()->user()->role !== 'administrador', 403);

        $asset = Asset::findOrFail($request->id);

        $request->validate([
            'id'     => 'required|integer|exists:assets,id',
            'nombre' => 'required|string|max:255|unique:assets,name,' . $asset->id,
            'imagen' => 'nullable|image|max:2048'
        ]);

        // ✅ Guardar valores anteriores
        $oldValues = [
            'name' => $asset->name,
            'image' => $asset->image,
        ];

        // Procesar imagen si viene una nueva
        if ($request->hasFile('imagen')) {
            // Borrar imagen anterior
            if ($asset->image && Storage::disk('public')->exists($asset->image)) {
                Storage::disk('public')->delete($asset->image);
            }

            // Guardar nueva
            $asset->image = $request->file('imagen')->store('assets/goods', 'public');
        }

        // Actualizar nombre
        $asset->name = $request->nombre;

        $asset->save();

        // ✅ Registrar actividad
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
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        abort_if(auth()->user()->role !== 'administrador', 403);

        $asset = Asset::find($id);

        if (!$asset) {
            return response()->json([
                'success' => false,
                'message' => 'Bien no encontrado.'
            ], 404);
        }

        // Obtener cantidad total (igual que tu vista SQL)
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

        $assetName = $asset->name; // Guardar antes de eliminar

        // Eliminar imagen
        if ($asset->image && Storage::disk('public')->exists($asset->image)) {
            Storage::disk('public')->delete($asset->image);
        }

        $asset->delete();

        // ✅ Registrar actividad
        ActivityLogger::deleted(Asset::class, $id, $assetName);

        return response()->json([
            'success' => true,
            'message' => 'Bien eliminado correctamente.'
        ]);
    }

    /**
     * Batch create goods from Excel upload
     * POST /api/goods/batchCreate
     *
     * Recibe un array de bienes con sus datos e imágenes opcionales
     * Formato esperado:
     * - goods[0][nombre]: nombre del bien
     * - goods[0][tipo]: tipo (1 = Cantidad, 2 = Serial)
     * - goods_0_imagen: archivo de imagen (opcional)
     */
    public function batchCreate(Request $request)
    {
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
            $createdAssets = []; // Para registrar en el log

            foreach ($goods as $index => $good) {
                try {
                    // Validar datos
                    $validator = validator($good, [
                        'nombre' => 'required|string|unique:assets,name',
                        'tipo' => 'required|in:1,2'
                    ]);

                    if ($validator->fails()) {
                        $errors[] = "Fila {$index}: " . $validator->errors()->first();
                        continue;
                    }

                    // Procesar imagen
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

                    // Crear el bien
                    $asset = Asset::create([
                        'name' => $good['nombre'],
                        'type' => (int)$good['tipo'] === 1 ? 'Cantidad' : 'Serial',
                        'image' => $imagePath
                    ]);

                    $createdAssets[] = $asset->name;
                    $created++;

                } catch (\Exception $e) {
                    $errors[] = "Fila {$index}: {$e->getMessage()}";
                }
            }

            // ✅ Registrar actividad masiva
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
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download Excel template for goods import
     * GET /api/goods/download-template
     *
     * Genera una plantilla Excel con las columnas: Bien y Tipo
     * El tipo puede ser "Cantidad" o "Serial"
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
     * Show the view for uploading goods via Excel
     * GET /api/goods/excel-upload/view
     *
     * Muestra la vista para la subida de bienes por medio de un archivo Excel
     */
    public function excelUploadView(Request $request)
    {
        if ($request->ajax()) {
            // si es una carga AJAX, solo renderiza el contenido interno
            return view('goods.excel-upload')
                ->renderSections()['content'];
        }

        return view('goods.excel-upload');
    }
}