<?php

use App\Http\Controllers\API\AddressController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\CartController;
use App\Http\Controllers\API\CategoryController;
use App\Http\Controllers\API\OrderController;
use App\Http\Controllers\API\ProductController;
use App\Http\Controllers\API\ProfileController;
use App\Http\Controllers\API\SupplierDashboardController;
use App\Http\Controllers\API\SupplierOrderController;
use App\Http\Controllers\API\SupplierProductController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — /api/v1/
|--------------------------------------------------------------------------
|
| Architecture decision: All routes are versioned under /api/v1/.
| This allows future /api/v2/ without breaking existing clients.
|
*/

// ─── Public Routes ────────────────────────────────────────

Route::prefix('v1')->group(function () {
    // Auth
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);

    // Health check
    Route::get('/health', fn () => response()->json(['status' => 'ok', 'version' => '1.0.0']));

    // Categories (public)
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/categories/{category}', [CategoryController::class, 'show']);
    Route::get('/categories/{category}/products', [CategoryController::class, 'products']);

    // Products (public)
    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/featured', [ProductController::class, 'featured']);
    Route::get('/products/new-arrivals', [ProductController::class, 'newArrivals']);
    Route::get('/products/best-sellers', [ProductController::class, 'bestSellers']);
    Route::get('/products/search', [ProductController::class, 'search']);
    Route::get('/products/{product}', [ProductController::class, 'show']);
});

// ─── Protected Routes ─────────────────────────────────────

Route::prefix('v1')->middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);

    // Profile
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::put('/profile', [ProfileController::class, 'update']);
    Route::post('/profile/avatar', [ProfileController::class, 'avatar']);
    Route::post('/profile/change-password', [ProfileController::class, 'changePassword']);
    Route::delete('/profile', [ProfileController::class, 'destroy']);

    // Addresses
    Route::apiResource('/addresses', AddressController::class)->except(['show']);

    // Cart
    Route::get('/cart', [CartController::class, 'index']);
    Route::post('/cart', [CartController::class, 'store']);
    Route::put('/cart/{cartItem}', [CartController::class, 'update']);
    Route::delete('/cart/{cartItem}', [CartController::class, 'destroy']);
    Route::delete('/cart', [CartController::class, 'clear']);

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
        // Supplier Dashboard
        Route::get('/dashboard', [SupplierDashboardController::class, 'index']);

        // Supplier Orders
        Route::get('/orders', [SupplierOrderController::class, 'index']);
        Route::get('/orders/{order}', [SupplierOrderController::class, 'show']);
        Route::post('/orders/{order}/accept', [SupplierOrderController::class, 'accept']);
        Route::post('/orders/{order}/reject', [SupplierOrderController::class, 'reject']);
        Route::post('/orders/{order}/process', [SupplierOrderController::class, 'process']);
        Route::post('/orders/{order}/ready', [SupplierOrderController::class, 'ready']);
        Route::post('/orders/{order}/ship', [SupplierOrderController::class, 'ship']);

        // Supplier Products
        Route::get('/products', [SupplierProductController::class, 'index']);
        Route::post('/products', [SupplierProductController::class, 'store']);
        Route::put('/products/{product}', [SupplierProductController::class, 'update']);
        Route::delete('/products/{product}', [SupplierProductController::class, 'destroy']);
        Route::patch('/products/{product}/stock', [SupplierProductController::class, 'updateStock']);
        Route::post('/products/{product}/image', [SupplierProductController::class, 'uploadImage']);
        Route::delete('/products/{product}/image', [SupplierProductController::class, 'deleteImage']);
    });

// ─── Admin Routes ─────────────────────────────────────────

Route::prefix('v1/admin')
    ->middleware(['auth:sanctum', 'role:admin'])
    ->group(function () {
        // TODO Phase 2: Admin endpoints
    });
