<?php

use Illuminate\Support\Facades\Route;
// use Laravel\Fortify\Features;
// use Livewire\Volt\Volt;
use App\Http\Controllers\{
    HomeController,
    TaskController,
    GoodsController,
    InventoryController,
    ReportController,
    UserController,
    RecordController
};

// redirect to home
Route::get('/', function () {
    return redirect()->route('home.index');
});

Route::get('home', [HomeController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('home.index');

// profile
Route::get('profile', function () {
    return 'Profile route';
})->name('profile');

// routes.index
Route::middleware('auth')->group(function () {
    Route::get('goods', [GoodsController::class, 'index'])->name('goods.index');
    // Route::get('inventories', [InventoryController::class, 'index'])->name('inventories.index');
    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('users', [UserController::class, 'index'])->name('users.index');
    Route::get('records', [RecordController::class, 'index'])->name('records.index');
});

// routes for goods
Route::post('/api/goods/create', [GoodsController::class, 'store'])->name('goods.store');
Route::post('/api/goods/update', [GoodsController::class, 'update'])->name('goods.update');
Route::delete('/api/goods/delete/{id}', [GoodsController::class, 'destroy'])->name('goods.destroy');


// API para las tareas
Route::prefix('api/tasks')->middleware('auth')->group(function () {
    Route::patch('toggle', [TaskController::class, 'toggle']);
    Route::delete('delete/{id}', [TaskController::class, 'destroy']);
    Route::post('store', [TaskController::class, 'store'])->name('tasks.store');
    Route::put('update', [TaskController::class, 'update'])->name('tasks.update');
});

// INVENTARIO (USANDO UN SOLO CONTROLADOR)
Route::controller(InventoryController::class)->group(function () {

    // 1. Grupos
    Route::get('/inventory/groups', 'groupIndex')->name('inventory.groups');

    // 2. Inventarios por grupo
    Route::get('/inventory/{group}/inventories', 'inventoryIndex')->name('inventory.inventories');

    // 3. Bienes por inventario
    Route::get('/inventory/{inventory}/goods', 'goodsIndex')->name('inventory.goods');

    // 4. Bienes seriales por bien en inventario
    Route::get('/inventory/{inventoryId}/goods/{assetId}/serials', 'serialsIndex')->name('inventory.serials');

});

// Route::middleware(['auth'])->group(function () {
//     Route::redirect('settings', 'settings/profile');

//     Volt::route('settings/profile', 'settings.profile')->name('profile.edit');
//     Volt::route('settings/password', 'settings.password')->name('user-password.edit');
//     Volt::route('settings/appearance', 'settings.appearance')->name('appearance.edit');

//     Volt::route('settings/two-factor', 'settings.two-factor')
//         ->middleware(
//             when(
//                 Features::canManageTwoFactorAuthentication()
//                     && Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword'),
//                 ['password.confirm'],
//                 [],
//             ),
//         )
//         ->name('two-factor.show');
// });
