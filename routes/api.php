<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\MenuController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// --- KİMLİK ---
//
// throttle:6,1 -> dakikada 6 istek. Varsayılan "api" grubu 60/dk'ya
// izin veriyor; bu, şifre denemesi için fazlasıyla geniş. Laravel'in
// kendi giriş ekranlarında da kullandığı sınır budur.
Route::middleware('throttle:6,1')->group(function () {
    Route::post('/sign-up', [AuthController::class, 'signUp']);
    Route::post('/sign-in', [AuthController::class, 'signIn']);
});

// --- MENÜ (herkese açık okuma) ---
Route::get('/categories', [MenuController::class, 'categories']);
Route::get('/customizations', [MenuController::class, 'customizations']);
Route::get('/menu-items', [MenuController::class, 'menuItems']);

// --- KORUMALI ---
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::post('/sign-out', [AuthController::class, 'signOut']);
});

// NOT: "/protected-endpoint" kaldırıldı. Sanctum'un çalıştığını
// göstermek için eklenmiş bir denemeydi; aynı işi /user zaten yapıyor.
