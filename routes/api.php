<?php

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::prefix('v1')->group(function () {
    // Routes d'authentification (non protégées)
    Route::prefix('auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/refresh', [AuthController::class, 'refresh']);
        Route::middleware('auth:api')->post('/logout', [AuthController::class, 'logout']);
    });

    // Routes protégées
    Route::middleware(['auth:api', 'log'])->group(function () {
        // Routes pour les administrateurs
        Route::middleware('role:admin')->prefix('admin')->group(function () {
            // Routes admin à implémenter
        });

        // Routes pour les distributeurs
        Route::middleware('role:distributeur')->prefix('distributeur')->group(function () {
            Route::post('/depot', [App\Http\Controllers\TransactionController::class, 'depot']);
            Route::post('/retrait', [App\Http\Controllers\TransactionController::class, 'retrait']);
            Route::get('/transactions', [App\Http\Controllers\TransactionController::class, 'index']);
            Route::get('/transactions/{transaction}', [App\Http\Controllers\TransactionController::class, 'show']);
        });

        // Routes pour les clients
        Route::middleware('role:client')->prefix('client')->group(function () {
            Route::get('/dashboard', [AuthController::class, 'dashboard']);
            Route::get('/solde', [App\Http\Controllers\TransactionController::class, 'getSolde']);
            Route::get('/transactions', [App\Http\Controllers\TransactionController::class, 'getTransactionsClient']);
            Route::post('/transfert', [App\Http\Controllers\TransactionController::class, 'transfert']);
            Route::post('/paiement', [App\Http\Controllers\PayementController::class, 'paiement']);
        });
    });
});
