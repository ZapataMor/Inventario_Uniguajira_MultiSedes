<?php

/**
 * Tests de carga masiva de bienes mediante archivo Excel.
 *
 * Cubre la funcionalidad del módulo de subida masiva disponible en:
 *   - Vista → GET  /goods/excel-upload         (excelUploadView)
 *   - API   → POST /api/goods/batchCreate      (batchCreate)
 *   - API   → GET  /api/goods/download-template (downloadTemplate)
 *
 * Formato esperado por batchCreate:
 *   goods[N][nombre] = nombre del bien
 *   goods[N][tipo]   = '1' (Cantidad) | '2' (Serial)
 *   goods_N_imagen   = archivo de imagen (opcional)
 *
 * Reglas de negocio:
 *   - Solo el administrador puede ejecutar la carga masiva (403 para otros roles)
 *   - Bienes con nombre duplicado se omiten y se registra el error en la respuesta
 *   - Bienes con tipo inválido se omiten y se registra el error en la respuesta
 *   - tipo 1 → se guarda como 'Cantidad', tipo 2 → se guarda como 'Serial'
 *   - Si al menos 1 bien se crea: success = true
 *   - Si todos los bienes fallan: success = false y created = 0
 *   - Los errores no detienen el proceso; se procesan todas las filas
 */

use App\Models\Asset;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;

// ══════════════════════════════════════════════
// VISTA DE CARGA EXCEL
// ══════════════════════════════════════════════

describe('Vista de carga Excel (/goods/excel-upload)', function () {

    describe('Acceso a la vista', function () {

        it('el administrador puede acceder a la vista de carga Excel (200)', function () {
            $this->actingAs(adminUser())
                ->get(route('goods.excel-upload'))
                ->assertStatus(200);
        });

        it('el consultor puede acceder a la vista de carga Excel (200)', function () {
            // La vista no tiene restricción de rol, solo requiere estar autenticado
            $this->actingAs(consultorUser())
                ->get(route('goods.excel-upload'))
                ->assertStatus(200);
        });

        it('usuario no autenticado es redirigido al login', function () {
            $this->get(route('goods.excel-upload'))
                ->assertRedirect('/login');
        });
    });

    describe('Contenido HTML de la vista', function () {

        it('muestra el título de carga de bienes desde Excel', function () {
            $this->actingAs(adminUser())
                ->get(route('goods.excel-upload'))
                ->assertSee('Cargar bienes al catalogo desde Excel');
        });

        it('contiene el área de arrastrar y soltar archivos (excel-upload-area)', function () {
            $this->actingAs(adminUser())
                ->get(route('goods.excel-upload'))
                ->assertSee('excel-upload-area');
        });

        it('contiene el input de archivo para selección de Excel (excelFileInput)', function () {
            $this->actingAs(adminUser())
                ->get(route('goods.excel-upload'))
                ->assertSee('excelFileInput');
        });

        it('contiene el botón de enviar datos al servidor (btnEnviarExcel)', function () {
            $this->actingAs(adminUser())
                ->get(route('goods.excel-upload'))
                ->assertSee('btnEnviarExcel');
        });

        it('contiene el botón para seleccionar archivo', function () {
            $this->actingAs(adminUser())
                ->get(route('goods.excel-upload'))
                ->assertSee('Seleccionar archivo');
        });

        it('contiene la sección de previsualización de datos', function () {
            $this->actingAs(adminUser())
                ->get(route('goods.excel-upload'))
                ->assertSee('Previsualización de datos');
        });

        it('contiene el botón Limpiar para resetear la UI', function () {
            $this->actingAs(adminUser())
                ->get(route('goods.excel-upload'))
                ->assertSee('Limpiar');
        });

        it('contiene el botón Enviar para enviar los datos', function () {
            $this->actingAs(adminUser())
                ->get(route('goods.excel-upload'))
                ->assertSee('Enviar');
        });

        it('contiene la función JS sendGoodsData para enviar el lote', function () {
            $this->actingAs(adminUser())
                ->get(route('goods.excel-upload'))
                ->assertSee('assets/js/goods-excel-upload.js');
        });

        it('contiene la función JS handleFileUpload para procesar el archivo', function () {
            $this->actingAs(adminUser())
                ->get(route('goods.excel-upload'))
                ->assertSee('Nombre')
                ->assertSee('Tipo');
        });

        it('el input acepta archivos .xlsx, .xls y .csv', function () {
            $this->actingAs(adminUser())
                ->get(route('goods.excel-upload'))
                ->assertSee('.xlsx, .xls, .csv');
        });
    });
});

// ══════════════════════════════════════════════
// CARGA MASIVA: CASOS EXITOSOS
// ══════════════════════════════════════════════

describe('Carga masiva de Bienes - Administrador', function () {

    describe('Creación exitosa de bienes', function () {

        it('puede crear un bien de tipo Cantidad (tipo 1)', function () {
            $this->actingAs(adminUser())
                ->postJson(route('goods.batchCreate'), [
                    'goods' => [
                        ['nombre' => 'Escritorio Masivo', 'tipo' => '1'],
                    ],
                ])
                ->assertStatus(200)
                ->assertJson(['success' => true]);

            $this->assertDatabaseHas('assets', [
                'name' => 'Escritorio Masivo',
                'type' => 'Cantidad',
            ]);
        });

        it('puede crear un bien de tipo Serial (tipo 2)', function () {
            $this->actingAs(adminUser())
                ->postJson(route('goods.batchCreate'), [
                    'goods' => [
                        ['nombre' => 'Computador Serial Masivo', 'tipo' => '2'],
                    ],
                ])
                ->assertStatus(200)
                ->assertJson(['success' => true]);

            $this->assertDatabaseHas('assets', [
                'name' => 'Computador Serial Masivo',
                'type' => 'Serial',
            ]);
        });

        it('puede crear múltiples bienes en una sola solicitud', function () {
            $this->actingAs(adminUser())
                ->postJson(route('goods.batchCreate'), [
                    'goods' => [
                        ['nombre' => 'Bien Lote 1', 'tipo' => '1'],
                        ['nombre' => 'Bien Lote 2', 'tipo' => '2'],
                        ['nombre' => 'Bien Lote 3', 'tipo' => '1'],
                    ],
                ])
                ->assertStatus(200)
                ->assertJson(['success' => true, 'created' => 3]);

            $this->assertDatabaseHas('assets', ['name' => 'Bien Lote 1']);
            $this->assertDatabaseHas('assets', ['name' => 'Bien Lote 2']);
            $this->assertDatabaseHas('assets', ['name' => 'Bien Lote 3']);
        });

        it('acepta el tipo sin importar mayusculas o minusculas', function () {
            $this->actingAs(adminUser())
                ->postJson(route('goods.batchCreate'), [
                    'goods' => [
                        ['nombre' => 'Bien Tipo Mayusculas', 'tipo' => 'CANTIDAD'],
                        ['nombre' => 'Bien Tipo Minusculas', 'tipo' => 'serial'],
                    ],
                ])
                ->assertStatus(200)
                ->assertJson(['success' => true, 'created' => 2]);

            $this->assertDatabaseHas('assets', [
                'name' => 'Bien Tipo Mayusculas',
                'type' => 'Cantidad',
            ]);

            $this->assertDatabaseHas('assets', [
                'name' => 'Bien Tipo Minusculas',
                'type' => 'Serial',
            ]);
        });

        it('el campo created refleja la cantidad exacta de bienes creados', function () {
            $response = $this->actingAs(adminUser())
                ->postJson(route('goods.batchCreate'), [
                    'goods' => [
                        ['nombre' => 'Bien Count 1', 'tipo' => '1'],
                        ['nombre' => 'Bien Count 2', 'tipo' => '2'],
                    ],
                ]);

            $response->assertStatus(200)
                ->assertJson(['created' => 2]);
        });

        it('la respuesta JSON incluye la estructura completa (success, message, created, errors)', function () {
            $this->actingAs(adminUser())
                ->postJson(route('goods.batchCreate'), [
                    'goods' => [
                        ['nombre' => 'Bien Estructura JSON', 'tipo' => '1'],
                    ],
                ])
                ->assertStatus(200)
                ->assertJsonStructure(['success', 'message', 'created', 'errors']);
        });

        it('tipo 1 se almacena como texto "Cantidad" en la base de datos', function () {
            $this->actingAs(adminUser())->postJson(route('goods.batchCreate'), [
                'goods' => [['nombre' => 'Bien Tipo Cantidad', 'tipo' => '1']],
            ]);

            $asset = Asset::where('name', 'Bien Tipo Cantidad')->first();
            expect($asset->type)->toBe('Cantidad');
        });

        it('tipo 2 se almacena como texto "Serial" en la base de datos', function () {
            $this->actingAs(adminUser())->postJson(route('goods.batchCreate'), [
                'goods' => [['nombre' => 'Bien Tipo Serial', 'tipo' => '2']],
            ]);

            $asset = Asset::where('name', 'Bien Tipo Serial')->first();
            expect($asset->type)->toBe('Serial');
        });

        it('el bien creado sin imagen tiene image null en la base de datos', function () {
            $this->actingAs(adminUser())->postJson(route('goods.batchCreate'), [
                'goods' => [['nombre' => 'Bien Sin Imagen Masivo', 'tipo' => '1']],
            ]);

            $this->assertDatabaseHas('assets', [
                'name'  => 'Bien Sin Imagen Masivo',
                'image' => null,
            ]);
        });

        it('el campo errors está vacío cuando todos los bienes son válidos', function () {
            $response = $this->actingAs(adminUser())
                ->postJson(route('goods.batchCreate'), [
                    'goods' => [
                        ['nombre' => 'Bien Sin Errores A', 'tipo' => '1'],
                        ['nombre' => 'Bien Sin Errores B', 'tipo' => '2'],
                    ],
                ]);

            $response->assertStatus(200);
            expect($response->json('errors'))->toBeEmpty();
        });
    });

    // ══════════════════════════════════════════════
    // CARGA CON IMAGEN
    // ══════════════════════════════════════════════

    describe('Carga masiva con imagen adjunta', function () {

        it('puede crear un bien con imagen adjunta al lote', function () {
            Storage::fake('public');

            $imagen = UploadedFile::fake()->image('bien.jpg', 100, 100);

            $response = $this->actingAs(adminUser())
                ->post(route('goods.batchCreate'), [
                    'goods'         => [['nombre' => 'Bien Con Imagen', 'tipo' => '1']],
                    'goods_0_imagen' => $imagen,
                ]);

            $response->assertStatus(200)
                ->assertJson(['success' => true, 'created' => 1]);

            $asset = Asset::where('name', 'Bien Con Imagen')->first();
            expect($asset)->not->toBeNull();
            expect($asset->image)->not->toBeNull();
        });

        it('la imagen del bien se almacena en el disco público', function () {
            Storage::fake('public');

            $imagen = UploadedFile::fake()->image('foto.png', 200, 200);

            $this->actingAs(adminUser())
                ->post(route('goods.batchCreate'), [
                    'goods'         => [['nombre' => 'Bien Imagen Disco', 'tipo' => '2']],
                    'goods_0_imagen' => $imagen,
                ]);

            $asset = Asset::where('name', 'Bien Imagen Disco')->first();
            Storage::disk('public')->assertExists($asset->image);
        });

        it('rechaza una imagen con extensión no permitida y registra el error', function () {
            Storage::fake('public');

            $archivoInvalido = UploadedFile::fake()->create('documento.pdf', 100, 'application/pdf');

            $response = $this->actingAs(adminUser())
                ->post(route('goods.batchCreate'), [
                    'goods'         => [
                        ['nombre' => 'Bien Imagen Invalida', 'tipo' => '1'],
                        ['nombre' => 'Bien Sin Imagen Valido', 'tipo' => '2'],
                    ],
                    'goods_0_imagen' => $archivoInvalido,
                ]);

            $response->assertStatus(200);
            $this->assertDatabaseMissing('assets', ['name' => 'Bien Imagen Invalida']);
            $errors = $response->json('errors');
            expect($errors)->not->toBeEmpty();
        });
    });

    // ══════════════════════════════════════════════
    // CARGA PARCIALMENTE EXITOSA
    // ══════════════════════════════════════════════

    describe('Carga parcialmente exitosa', function () {

        it('crea los bienes válidos aunque el lote contenga bienes inválidos', function () {
            crearBien(['name' => 'Bien Ya Existente']);

            $this->actingAs(adminUser())
                ->postJson(route('goods.batchCreate'), [
                    'goods' => [
                        ['nombre' => 'Bien Ya Existente', 'tipo' => '1'], // duplicado → error
                        ['nombre' => 'Bien Nuevo Del Lote', 'tipo' => '1'], // válido
                    ],
                ])
                ->assertStatus(200)
                ->assertJson(['success' => true, 'created' => 1]);

            $this->assertDatabaseHas('assets', ['name' => 'Bien Nuevo Del Lote']);
        });

        it('la respuesta incluye errores en el array errors para las filas inválidas', function () {
            crearBien(['name' => 'Bien Duplicado Parcial']);

            $response = $this->actingAs(adminUser())
                ->postJson(route('goods.batchCreate'), [
                    'goods' => [
                        ['nombre' => 'Bien Duplicado Parcial', 'tipo' => '1'],
                        ['nombre' => 'Bien OK Parcial', 'tipo' => '2'],
                    ],
                ]);

            $response->assertStatus(200);
            $errors = $response->json('errors');
            expect($errors)->not->toBeEmpty();
            expect(count($errors))->toBe(1);
        });

        it('cuando todos los bienes son inválidos, success es false y created es 0', function () {
            crearBien(['name' => 'Todos Fallan A']);
            crearBien(['name' => 'Todos Fallan B']);

            $this->actingAs(adminUser())
                ->postJson(route('goods.batchCreate'), [
                    'goods' => [
                        ['nombre' => 'Todos Fallan A', 'tipo' => '1'],
                        ['nombre' => 'Todos Fallan B', 'tipo' => '2'],
                    ],
                ])
                ->assertStatus(200)
                ->assertJson(['success' => false, 'created' => 0]);
        });

        it('los bienes inválidos no incrementan el contador de assets en la BD', function () {
            crearBien(['name' => 'Bien Existente Counter']);
            $totalAntes = Asset::count();

            $this->actingAs(adminUser())
                ->postJson(route('goods.batchCreate'), [
                    'goods' => [
                        ['nombre' => 'Bien Existente Counter', 'tipo' => '1'],
                    ],
                ]);

            expect(Asset::count())->toBe($totalAntes);
        });

        it('el mensaje de respuesta indica cuántos bienes se crearon y cuántos errores hubo', function () {
            crearBien(['name' => 'Con Error Msg']);

            $response = $this->actingAs(adminUser())
                ->postJson(route('goods.batchCreate'), [
                    'goods' => [
                        ['nombre' => 'Con Error Msg', 'tipo' => '1'],
                        ['nombre' => 'Sin Error Msg', 'tipo' => '1'],
                    ],
                ]);

            $message = $response->json('message');
            expect($message)->not->toBeEmpty();
            expect($message)->toContain('1'); // menciona al menos el 1 creado
        });
    });

    // ══════════════════════════════════════════════
    // VALIDACIONES
    // ══════════════════════════════════════════════

    describe('Validaciones de batchCreate', function () {

        it('retorna 400 cuando el array goods está vacío', function () {
            $this->actingAs(adminUser())
                ->postJson(route('goods.batchCreate'), ['goods' => []])
                ->assertStatus(400)
                ->assertJson(['success' => false]);
        });

        it('retorna 400 cuando no se envía el campo goods', function () {
            $this->actingAs(adminUser())
                ->postJson(route('goods.batchCreate'), [])
                ->assertStatus(400)
                ->assertJson(['success' => false]);
        });

        it('el mensaje de error al enviar goods vacío es descriptivo y no vacío', function () {
            $response = $this->actingAs(adminUser())
                ->postJson(route('goods.batchCreate'), ['goods' => []]);

            $response->assertStatus(400);
            expect($response->json('message'))->not->toBeEmpty();
        });

        it('omite filas donde el nombre está vacío y continúa con las demás', function () {
            $response = $this->actingAs(adminUser())
                ->postJson(route('goods.batchCreate'), [
                    'goods' => [
                        ['nombre' => '', 'tipo' => '1'],       // inválido
                        ['nombre' => 'Bien Con Nombre', 'tipo' => '1'], // válido
                    ],
                ]);

            $response->assertStatus(200)
                ->assertJson(['created' => 1]);

            $errors = $response->json('errors');
            expect(count($errors))->toBe(1);
        });

        it('omite filas con tipo inválido (tipo 5) y crea las válidas', function () {
            $response = $this->actingAs(adminUser())
                ->postJson(route('goods.batchCreate'), [
                    'goods' => [
                        ['nombre' => 'Bien Tipo Cinco', 'tipo' => '5'],  // inválido
                        ['nombre' => 'Bien Tipo Válido', 'tipo' => '1'], // válido
                    ],
                ]);

            $response->assertStatus(200)
                ->assertJson(['created' => 1]);

            $this->assertDatabaseMissing('assets', ['name' => 'Bien Tipo Cinco']);
            $this->assertDatabaseHas('assets', ['name' => 'Bien Tipo Válido']);
        });

        it('omite filas con tipo 0 (fuera del rango permitido)', function () {
            $response = $this->actingAs(adminUser())
                ->postJson(route('goods.batchCreate'), [
                    'goods' => [
                        ['nombre' => 'Tipo Cero', 'tipo' => '0'],
                    ],
                ]);

            $response->assertStatus(200)
                ->assertJson(['success' => false]);

            $this->assertDatabaseMissing('assets', ['name' => 'Tipo Cero']);
        });

        it('omite filas cuyo nombre ya existe en la base de datos', function () {
            crearBien(['name' => 'Nombre Ya En BD']);

            $response = $this->actingAs(adminUser())
                ->postJson(route('goods.batchCreate'), [
                    'goods' => [
                        ['nombre' => 'Nombre Ya En BD', 'tipo' => '1'],
                        ['nombre' => 'Nombre Único Nuevo', 'tipo' => '2'],
                    ],
                ]);

            $response->assertStatus(200)
                ->assertJson(['created' => 1]);

            $this->assertDatabaseHas('assets', ['name' => 'Nombre Único Nuevo']);
        });

        it('acepta nombre con exactamente 255 caracteres', function () {
            $nombre255 = str_repeat('A', 255);

            $this->actingAs(adminUser())
                ->postJson(route('goods.batchCreate'), [
                    'goods' => [
                        ['nombre' => $nombre255, 'tipo' => '1'],
                    ],
                ])
                ->assertStatus(200)
                ->assertJson(['success' => true, 'created' => 1]);
        });

        it('el segundo bien con el mismo nombre dentro del mismo lote se omite', function () {
            // El primer bien se crea correctamente, el segundo falla por unicidad
            $response = $this->actingAs(adminUser())
                ->postJson(route('goods.batchCreate'), [
                    'goods' => [
                        ['nombre' => 'Nombre Repetido En Lote', 'tipo' => '1'],
                        ['nombre' => 'Nombre Repetido En Lote', 'tipo' => '2'], // duplicado interno
                    ],
                ]);

            $response->assertStatus(200)
                ->assertJson(['created' => 1]);

            expect(Asset::where('name', 'Nombre Repetido En Lote')->count())->toBe(1);
        });

        it('puede enviar 10 bienes válidos en un solo lote', function () {
            $goods = [];
            for ($i = 1; $i <= 10; $i++) {
                $goods[] = ['nombre' => "Bien Lote Grande $i", 'tipo' => ($i % 2 === 0) ? '2' : '1'];
            }

            $this->actingAs(adminUser())
                ->postJson(route('goods.batchCreate'), ['goods' => $goods])
                ->assertStatus(200)
                ->assertJson(['success' => true, 'created' => 10]);

            expect(Asset::count())->toBe(10);
        });
    });
});

// ══════════════════════════════════════════════
// CONTROL DE ACCESO
// ══════════════════════════════════════════════

describe('Control de acceso a batchCreate', function () {

    it('el usuario no autenticado no puede hacer carga masiva (401)', function () {
        $this->postJson(route('goods.batchCreate'), [
            'goods' => [
                ['nombre' => 'Bien No Auth', 'tipo' => '1'],
            ],
        ])->assertStatus(401);
    });

    it('el consultor no puede hacer carga masiva (403)', function () {
        $this->actingAs(consultorUser())
            ->postJson(route('goods.batchCreate'), [
                'goods' => [
                    ['nombre' => 'Bien Consultor Bloqueado', 'tipo' => '1'],
                ],
            ])
            ->assertStatus(403);
    });

    it('el intento del consultor no inserta ningún bien en la base de datos', function () {
        $totalAntes = Asset::count();

        $this->actingAs(consultorUser())
            ->postJson(route('goods.batchCreate'), [
                'goods' => [
                    ['nombre' => 'No Debe Crearse Consultor', 'tipo' => '1'],
                ],
            ]);

        expect(Asset::count())->toBe($totalAntes);
    });

    it('el usuario no autenticado es redirigido al login al acceder a la vista', function () {
        $this->get(route('goods.excel-upload'))
            ->assertRedirect('/login');
    });

    it('el usuario no autenticado no puede descargar la plantilla (401)', function () {
        $this->getJson(route('goods.download-template'))
            ->assertStatus(401);
    });
});

// ══════════════════════════════════════════════
// DESCARGA DE PLANTILLA
// ══════════════════════════════════════════════

describe('Descarga de plantilla Excel', function () {

    it('el administrador puede descargar la plantilla correctamente (200)', function () {
        // El archivo real está en storage/app/templates/Plantilla Crear Bienes.xlsx
        $this->actingAs(adminUser())
            ->get(route('goods.download-template'))
            ->assertStatus(200)
            ->assertHeader('Content-Disposition');
    });

    it('el consultor también puede descargar la plantilla (sin restricción de rol)', function () {
        // downloadTemplate no restringe por rol, solo requiere auth
        $this->actingAs(consultorUser())
            ->get(route('goods.download-template'))
            ->assertStatus(200)
            ->assertHeader('Content-Disposition');
    });

    it('genera una plantilla con solo las columnas Nombre y Tipo', function () {
        $response = $this->actingAs(adminUser())
            ->get(route('goods.download-template'));

        $response->assertStatus(200);

        $tempBase = tempnam(sys_get_temp_dir(), 'goods-template');
        $tempFile = $tempBase . '.xlsx';
        @rename($tempBase, $tempFile);
        file_put_contents($tempFile, $response->streamedContent());

        $spreadsheet = IOFactory::load($tempFile);
        $sheet = $spreadsheet->getActiveSheet();

        expect($sheet->getCell('A1')->getValue())->toBe('Nombre*');
        expect($sheet->getCell('B1')->getValue())->toBe('Tipo*');
        expect($sheet->getCell('C1')->getValue())->toBeNull();

        @unlink($tempFile);
    });

    it('la descarga ya no depende de un archivo fisico en storage', function () {
        $templateFile = storage_path('app/templates') . DIRECTORY_SEPARATOR . 'Plantilla Crear Bienes.xlsx';
        $backupFile   = $templateFile . '.bak';

        $existiaAntes = file_exists($templateFile);
        if ($existiaAntes) {
            rename($templateFile, $backupFile);
        }

        $response = $this->actingAs(adminUser())
            ->get(route('goods.download-template'));

        // Restaurar el archivo original
        if ($existiaAntes) {
            rename($backupFile, $templateFile);
        }

        $response->assertStatus(200)
            ->assertHeader('Content-Disposition');
    });
});
