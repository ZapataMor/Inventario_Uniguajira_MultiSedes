<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Asset;
use App\Models\AssetInventory;
use Illuminate\Support\Facades\Storage;

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
        $request->validate([
            'nombre' => 'required|string|max:255',
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
            'type'  => $request->tipo,
            'image' => $path
        ]);

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
        $request->validate([
            'id'     => 'required|integer|exists:assets,id',
            'nombre' => 'required|string|max:255',
            'imagen' => 'nullable|image|max:2048'
        ]);

        $asset = Asset::findOrFail($request->id);

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

        // Eliminar imagen
        if ($asset->image && Storage::disk('public')->exists($asset->image)) {
            Storage::disk('public')->delete($asset->image);
        }

        $asset->delete();

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
        try {
            // Obtener todos los datos del request
            $allData = $request->all();
            
            // Obtener los bienes del request (puede venir como array anidado)
            $goods = $request->input('goods', []);
            
            // Si goods está vacío, intentar obtenerlo de otra forma
            if (empty($goods) && isset($allData['goods'])) {
                $goods = $allData['goods'];
            }
            
            if (empty($goods) || !is_array($goods)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se recibieron datos válidos para procesar.'
                ], 400);
            }

            $created = 0;
            $errors = [];
            $createdAssets = [];

            // Procesar cada bien
            foreach ($goods as $index => $good) {
                try {
                    // Validar que good sea un array
                    if (!is_array($good)) {
                        $errors[] = "Fila {$index}: Formato de datos inválido";
                        continue;
                    }

                    // Validar datos básicos
                    $nombre = isset($good['nombre']) ? trim($good['nombre']) : '';
                    $tipo = isset($good['tipo']) ? $good['tipo'] : null;
                    
                    if (empty($nombre) || $tipo === null) {
                        $errors[] = "Fila {$index}: Faltan datos requeridos (nombre o tipo)";
                        continue;
                    }

                    // Validar tipo
                    $tipo = (int) $tipo;
                    if (!in_array($tipo, [1, 2])) {
                        $errors[] = "Fila {$index}: Tipo inválido. Debe ser 1 (Cantidad) o 2 (Serial)";
                        continue;
                    }

                    // Verificar si el bien ya existe
                    $existingAsset = Asset::where('name', $nombre)->first();
                    if ($existingAsset) {
                        $errors[] = "Fila {$index}: El bien '{$nombre}' ya existe";
                        continue;
                    }

                    // Procesar imagen si existe
                    $imagePath = null;
                    $imageKey = "goods_{$index}_imagen";
                    
                    if ($request->hasFile($imageKey)) {
                        $image = $request->file($imageKey);
                        
                        // Validar que sea una imagen
                        if ($image->isValid()) {
                            // Validar tamaño (2 MB)
                            if ($image->getSize() <= 2048 * 1024) {
                                // Validar que sea realmente una imagen
                                $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/jpg'];
                                if (in_array($image->getMimeType(), $allowedMimes)) {
                                    $imagePath = $image->store('assets/goods', 'public');
                                } else {
                                    $errors[] = "Fila {$index}: El archivo debe ser una imagen válida (JPEG, PNG, GIF, WEBP)";
                                }
                            } else {
                                $errors[] = "Fila {$index}: La imagen excede el tamaño máximo (2MB)";
                            }
                        } else {
                            $errors[] = "Fila {$index}: La imagen es inválida";
                        }
                    }

                    // Crear el bien
                    $asset = Asset::create([
                        'name'  => $nombre,
                        'type'  => $tipo,
                        'image' => $imagePath
                    ]);

                    $created++;
                    $createdAssets[] = [
                        'id' => $asset->id,
                        'name' => $asset->name,
                        'type' => $asset->type
                    ];

                } catch (\Exception $e) {
                    $errors[] = "Fila {$index}: Error al crear bien - " . $e->getMessage();
                }
            }

            // Preparar respuesta
            $message = "Se crearon {$created} bien(es) exitosamente.";
            if (!empty($errors)) {
                $message .= " " . count($errors) . " error(es) encontrado(s).";
            }

            return response()->json([
                'success' => $created > 0,
                'message' => $message,
                'created' => $created,
                'errors' => $errors,
                'assets' => $createdAssets
            ], $created > 0 ? 200 : 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar la solicitud: ' . $e->getMessage()
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
        // Crear contenido Excel en formato SpreadsheetML (compatible con Excel)
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
<?mso-application progid="Excel.Sheet"?>
<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
 xmlns:o="urn:schemas-microsoft-com:office:office"
 xmlns:x="urn:schemas-microsoft-com:office:excel"
 xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"
 xmlns:html="http://www.w3.org/TR/REC-html40">
 <Styles>
  <Style ss:ID="Header">
   <Font ss:Bold="1"/>
   <Interior ss:Color="#CCCCCC" ss:Pattern="Solid"/>
  </Style>
 </Styles>
 <Worksheet ss:Name="Bienes">
  <Table>
   <Row>
    <Cell ss:StyleID="Header"><Data ss:Type="String">Bien</Data></Cell>
    <Cell ss:StyleID="Header"><Data ss:Type="String">Tipo</Data></Cell>
   </Row>
   <Row>
    <Cell><Data ss:Type="String">Ejemplo 1</Data></Cell>
    <Cell><Data ss:Type="String">Cantidad</Data></Cell>
   </Row>
   <Row>
    <Cell><Data ss:Type="String">Ejemplo 2</Data></Cell>
    <Cell><Data ss:Type="String">Serial</Data></Cell>
   </Row>
  </Table>
 </Worksheet>
</Workbook>';

        $filename = 'plantilla_bienes.xls';
        
        return response($xml, 200)
            ->header('Content-Type', 'application/vnd.ms-excel')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->header('Cache-Control', 'max-age=0');
    }
}
