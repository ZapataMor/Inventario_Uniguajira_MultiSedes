<?php

// bloquea cualquier acceso público a /register sin depender solo de Fortify
Route::match(['get','post'], 'register', function () {
    abort(404);
})->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    HomeController,
    TaskController,
    GoodsController,
    GroupController,
    InventoryController,
    GoodsInventoryController,
    ReportController,
    UserController,
    RecordController,
    ReportFolderController,
    RemovedController,
};

/**
 * Orden de las rutas:
 * 1. Home
 * 2. Tareas
 * 3. Bienes
 * 4. Excel upload
 * 5. Grupos
 * 6. Inventarios
 * 7. Bienes inventario
 * 8. Bienes serial inventario
 * 9. Dados de baja
 * 10. Carpetas
 * 11. Reportes
 * 12. Usuarios
 * 13. Historial
 * 14. Perfil
 */


/**
 * 1. Home
 * ----------------------------------------------------------------------------
 */

Route::get('home', [HomeController::class, 'index'])->middleware(['auth', 'verified'])->name('home.index');

Route::get('/', function () { return redirect()->route('home.index'); });


/**
 * 2. Tareas
 * ----------------------------------------------------------------------------
 */

Route::prefix('api/tasks')->group(function () {
    Route::patch('toggle', [TaskController::class, 'toggle']);
    Route::delete('delete/{id}', [TaskController::class, 'destroy']);
    Route::post('store', [TaskController::class, 'store'])->name('tasks.store');
    Route::put('update', [TaskController::class, 'update'])->name('tasks.update');
});


/**
 * 3. Bienes
 * ----------------------------------------------------------------------------
 */

Route::get('goods', [GoodsController::class, 'index'])->name('goods.index');

Route::prefix('api/goods')->group(function () {
    Route::post('create', [GoodsController::class, 'store'])->name('goods.store');
    Route::post('batchCreate', [GoodsController::class, 'batchCreate'])->name('goods.batchCreate');
    Route::post('update', [GoodsController::class, 'update'])->name('goods.update');
    Route::delete('delete/{id}', [GoodsController::class, 'destroy'])->name('goods.destroy');
    Route::get('get/json', [GoodsController::class, 'getJson']);
});


/**
 * 4. Excel upload
 * ----------------------------------------------------------------------------
 */

Route::get('/goods/excel-upload', [GoodsController::class, 'excelUploadView'])->name('goods.excel-upload');

Route::get('api/goods/download-template', [GoodsController::class, 'downloadTemplate'])->name('goods.download-template');


/**
 * 5. Grupos
 * ----------------------------------------------------------------------------
 */

Route::get('/groups', [GroupController::class, 'index'])->name('inventory.groups');

Route::prefix('api/groups')->group(function () {
    Route::get('getAll', [GroupController::class, 'getAll']);
    Route::post('create', [GroupController::class, 'store'])->name('groups.create');
    Route::post('rename', [GroupController::class, 'update'])->name('groups.rename');
    Route::delete('delete/{id}', [GroupController::class, 'destroy'])->name('groups.delete');
});


/**
 * 6. Inventarios
 * ----------------------------------------------------------------------------
 */

Route::get('/group/{groupId}', [InventoryController::class, 'index'])->name('inventory.inventories');

Route::prefix('api/inventories')->group(function () {
    Route::get('getByGroupId/{groupId}', [InventoryController::class, 'getByGroupId']);
    Route::post('create', [InventoryController::class, 'create'])->name('inventories.create');
    Route::post('rename', [InventoryController::class, 'rename'])->name('inventories.rename');
    Route::post('updateResponsable', [InventoryController::class, 'updateResponsable'])->name('inventories.updateResponsable');
    Route::post('updateEstado', [InventoryController::class, 'updateEstado'])->name('inventories.updateEstado');
    Route::delete('delete/{id}', [InventoryController::class, 'delete'])->name('inventories.delete');
});


/**
 * 7. Bienes inventario
 * ----------------------------------------------------------------------------
 */

Route::get('/group/{groupId}/inventory/{inventoryId}', [GoodsInventoryController::class, 'goodsIndex'])->name('inventory.goods');

Route::prefix('api/goods-inventory')->group(function () {
    Route::post('/create', [GoodsInventoryController::class, 'store']);
    Route::post('/update-quantity', [GoodsInventoryController::class, 'updateQuantity']);
    Route::delete('/delete-quantity/{inventoryId}/{goodId}', [GoodsInventoryController::class, 'deleteQuantity']);
    Route::delete('/delete-serial/{equipment}', [GoodsInventoryController::class, 'deleteSerial']);
    Route::post('/update-serial', [GoodsInventoryController::class, 'updateSerial'])->name('goods-inventory.update-serial');
    Route::post('/remove-good', [GoodsInventoryController::class, 'removeGood']);
    Route::post('/remove-good-serial', [GoodsInventoryController::class, 'removeGoodSerial']);
});


/**
 * 8. Bienes serial inventario
 * ----------------------------------------------------------------------------
 */

Route::get('/group/{groupId}/inventory/{inventoryId}/goods/{assetId}/serials', [InventoryController::class, 'serialsIndex'])->name('inventory.serials');


/**
 * 9. Dados de baja
 * ----------------------------------------------------------------------------
 */

Route::get('removed', [RemovedController::class, 'index'])->name('removed.index');

Route::get('removed/{id}', [RemovedController::class, 'show'])->name('removed.show');

Route::prefix('api/removed')->group(function () {
    Route::get('/filter', [RemovedController::class, 'filter'])->name('removed.filter');
    Route::get('/filter-options', [RemovedController::class, 'filterOptions'])->name('removed.filter-options');
    Route::get('/export', [RemovedController::class, 'export'])->name('removed.export');
    Route::get('/stats', [RemovedController::class, 'stats'])->name('removed.stats');
    Route::delete('/{id}', [RemovedController::class, 'destroy'])->name('removed.destroy');
});


/**
 * 10. Carpetas
 * ----------------------------------------------------------------------------
 */

Route::get('reports', [ReportFolderController::class, 'index'])->name('reports.index');
Route::prefix('api/folders')->group(function () {
    Route::post('create', [ReportFolderController::class, 'store']);
    Route::post('rename', [ReportFolderController::class, 'rename']);
    Route::delete('delete/{id}', [ReportFolderController::class, 'destroy']);
});


/**
 * 11. Reportes
 * ----------------------------------------------------------------------------
 */

Route::get('reports/folder/{folderId}', [ReportFolderController::class, 'show'])->name('reports.folder');
Route::prefix('api/reports')->group(function () {
    Route::get('getAll/{folderId}', [ReportController::class, 'getAll']);
    Route::post('create', [ReportController::class, 'store']);
    Route::post('rename', [ReportController::class, 'rename']);
    Route::delete('delete/{id}', [ReportController::class, 'destroy']);
    Route::post('download', [ReportController::class, 'download']);
});


/**
 * 12. Usuarios
 * ----------------------------------------------------------------------------
 */

Route::get('users', [UserController::class, 'index'])->name('users.index');

Route::prefix('api/users')->group(function () {
    Route::post('store', [UserController::class, 'store'])->name('users.store');
    Route::post('update', [UserController::class, 'update'])->name('users.update');
    Route::delete('delete/{id}', [UserController::class, 'destroy']);
});


/**
 * 13. Historial
 * ----------------------------------------------------------------------------
 */

Route::get('records', [RecordController::class, 'index'])->name('records.index');

Route::prefix('api/records')->group(function () {
    Route::delete('clean', [RecordController::class, 'clean'])->name('records.clean');
    Route::get('export', [RecordController::class, 'export'])->name('records.export');
});


/**
 * 14. Perfil
 * ----------------------------------------------------------------------------
 */

Route::get('profile', function () { return 'Profile route'; })->name('profile');
