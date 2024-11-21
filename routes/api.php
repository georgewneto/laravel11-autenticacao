<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Middleware\JwtMiddleware;
use App\Http\Controllers\PermissionController;

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
    //Rota para cadastrar e configurar permissões
    //Route::get('permission', [PermissionController::class, 'index']);
});
