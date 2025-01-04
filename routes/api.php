<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Middleware\JwtMiddleware;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\StartPermissionController;
use App\Http\Controllers\RoleController;

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

    Route::apiResource('roles', RoleController::class);
    Route::apiResource('permissions', PermissionController::class);

    // Rotas de Roles
    Route::post('roles/assign', [RoleController::class, 'assignRoleToUser']);
    Route::post('roles/remove', [RoleController::class, 'removeRoleFromUser']);
    // Rotas de Permissions
    Route::post('permissions/assign', [PermissionController::class, 'assignPermissionToUser']);
    Route::post('permissions/remove', [PermissionController::class, 'removePermissionFromUser']);

    //Rota para cadastrar e configurar permissões
    //Route::get('startpermission', [StartPermissionController::class, 'index']);
});
