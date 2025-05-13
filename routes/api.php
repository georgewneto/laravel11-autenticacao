<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Middleware\JwtMiddleware;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\StartPermissionController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;

/*
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
*/

Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);
Route::post('forgotpassword', [AuthController::class, 'forgotpassword']);

Route::middleware([JwtMiddleware::class])->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('me', [AuthController::class, 'me']);
    Route::post('updatepassword', [AuthController::class, 'updatepassword']);
    Route::post('refresh', [AuthController::class, 'refresh']);

    Route::group(['prefix' => 'users'], function () {
        Route::get('/', [UserController::class, 'index'])->name('users.index');
        Route::post('/store', [UserController::class, 'store'])->name('users.store');
        Route::get('/edit/{id}', [UserController::class, 'edit'])->name('users.edit');
        Route::post('/update', [UserController::class, 'update'])->name('users.update');
        Route::get('/delete/{id}', [UserController::class, 'delete'])->name('users.delete');
    });

    Route::apiResource('roles', RoleController::class);
    Route::apiResource('permissions', PermissionController::class);

    // Rotas de Roles
    Route::post('roles/assign', [RoleController::class, 'assignRoleToUser']);
    Route::post('roles/remove', [RoleController::class, 'removeRoleFromUser']);
    // Rotas de Permissions
    //Route::post('permissions/assign', [PermissionController::class, 'assignPermissionToUser']);
    //Route::post('permissions/remove', [PermissionController::class, 'removePermissionFromUser']);
    Route::post('permissions/assign', [PermissionController::class, 'assignPermissionToRole']);
    Route::post('permissions/remove', [PermissionController::class, 'removePermissionFromRole']);

    //Rota para cadastrar e configurar permissões
    //Route::get('startpermission', [StartPermissionController::class, 'index']);
});
