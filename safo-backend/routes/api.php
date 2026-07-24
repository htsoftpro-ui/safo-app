<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\OrderController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — /api/v1/
|--------------------------------------------------------------------------
|
| Architecture decision: All routes are versioned under /api/v1/.
| This allows future /api/v2/ without breaking existing clients.
|
| Route groups enforce:
| - sanctum auth (all protected routes)
| - role-based access (customer/supplier/admin)
|
*/

// ─── Public Routes ────────────────────────────────────────

Route::prefix('v1')->group(function () {
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);

    // Health check
    Route::get('/health', fn () => response()->json(['status' => 'ok', 'version' => '1.0.0']));
});

// ─── Protected Routes ─────────────────────────────────────

Route::prefix('v1')->middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);

    // Orders (customer)
    Route::get('/orders', [OrderController::class, 'index']);
    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders/{order}', [OrderController::class, 'show']);
    Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel']);
    Route::post('/orders/{order}/confirm-delivery', [OrderController::class, 'confirmDelivery']);
});

// ─── Supplier Routes ──────────────────────────────────────

Route::prefix('v1/supplier')
    ->middleware(['auth:sanctum', 'role:supplier'])
    ->group(function () {
        // TODO Phase 1: Supplier endpoints
        // Route::get('/dashboard', [SupplierDashboardController::class, 'index']);
        // Route::apiResource('/products', SupplierProductController::class);
        // Route::apiResource('/orders', SupplierOrderController::class);
    });

// ─── Admin Routes ─────────────────────────────────────────

Route::prefix('v1/admin')
    ->middleware(['auth:sanctum', 'role:admin'])
    ->group(function () {
        // TODO Phase 1: Admin endpoints
    });
