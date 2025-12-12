<?php

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
    RecordController
};

// Redirect to home
Route::get('/', function () {
    return redirect()->route('home.index');
});

// Home routes
Route::get('home', [HomeController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('home.index');

// Profile routes
Route::get('profile', function () {
    return 'Profile route';
})->name('profile');

// Rutas de navegacion
Route::middleware('auth')->group(function () {
    // General routes
    Route::get('goods', [GoodsController::class, 'index'])->name('goods.index');
    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('users', [UserController::class, 'index'])->name('users.index');
    Route::get('records', [RecordController::class, 'index'])->name('records.index');

    // Group routes
    Route::get('/groups', [GroupController::class, 'index'])->name('inventory.groups');

    // Inventory routes
    Route::controller(InventoryController::class)->group(function () {
        Route::get('/group/{groupId}', 'index')->name('inventory.inventories');
        Route::get('/group/{groupId}/inventory/{inventoryId}/goods/{assetId}/serials', 'serialsIndex')->name('inventory.serials');
    });

    Route::get('/group/{groupId}/inventory/{inventoryId}', [GoodsInventoryController::class, 'goodsIndex'])->name('inventory.goods');

});

// API routes
Route::prefix('api')->middleware('auth')->group(function () {
    // Tasks API
    Route::prefix('tasks')->group(function () {
        Route::patch('toggle', [TaskController::class, 'toggle']);
        Route::delete('delete/{id}', [TaskController::class, 'destroy']);
        Route::post('store', [TaskController::class, 'store'])->name('tasks.store');
        Route::put('update', [TaskController::class, 'update'])->name('tasks.update');
    });

    // Goods API
    Route::prefix('goods')->group(function () {
        Route::post('create', [GoodsController::class, 'store'])->name('goods.store');
        Route::post('update', [GoodsController::class, 'update'])->name('goods.update');
        Route::delete('delete/{id}', [GoodsController::class, 'destroy'])->name('goods.destroy');
        Route::get('get/json', [GoodsController::class, 'getJson']);
    });

    // Groups API
    Route::prefix('groups')->group(function () {
        Route::post('create', [GroupController::class, 'store'])->name('groups.create');
        Route::post('rename', [GroupController::class, 'update'])->name('groups.rename');
        Route::delete('delete/{id}', [GroupController::class, 'destroy'])->name('groups.delete');
    });

    // Inventories API
    Route::prefix('inventories')->group(function () {
        Route::post('create', [InventoryController::class, 'create'])->name('inventories.create');
        Route::post('rename', [InventoryController::class, 'rename'])->name('inventories.rename');
        Route::post('updateResponsable', [InventoryController::class, 'updateResponsable'])->name('inventories.updateResponsable');
        Route::post('updateEstado', [InventoryController::class, 'updateEstado'])->name('inventories.updateEstado');
        Route::delete('delete/{id}', [InventoryController::class, 'delete'])->name('inventories.delete');
    });

    Route::prefix('goods-inventory')->group(function () {

        // Crear bien en inventario
        Route::post('/create', [GoodsInventoryController::class, 'store']);

        // Actualizar cantidad
        Route::post('/update-quantity', [GoodsInventoryController::class, 'updateQuantity']);

        // Eliminar bien de tipo cantidad
        Route::delete('/delete-quantity/{inventoryId}/{goodId}', [GoodsInventoryController::class, 'deleteQuantity']);

        // Eliminar bien de tipo serial
        Route::delete('/delete-serial/{equipment}', [GoodsInventoryController::class, 'deleteSerial']);

        // Actualizar bien de tipo serial
        Route::post('/update-serial', [GoodsInventoryController::class, 'updateSerial'])
            ->name('goods-inventory.update-serial');

    });
});

