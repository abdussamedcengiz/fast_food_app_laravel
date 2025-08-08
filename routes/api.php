<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\MenuController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Auth
Route::post('/sign-up', [AuthController::class, 'signUp']);
Route::post('/sign-in', [AuthController::class, 'signIn']);

// Menü
Route::get('/categories', [MenuController::class, 'categories']);
Route::get('/customizations', [MenuController::class, 'customizations']);
Route::get('/menu-items', [MenuController::class, 'menuItems']);

// Korumalı örnek endpoint
Route::middleware('auth:sanctum')->get('/protected-endpoint', function (Request $request) {
    return response()->json([
        'message' => 'Bu endpoint korumalı!',
        'user' => $request->user()
    ]);
});
