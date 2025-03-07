<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\StoreLocationController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\StockReportController;
use App\Http\Controllers\AuthController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


// User routes with role-based access control
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/users', [UserController::class, 'create'])->middleware('role:admin,superadmin');
    Route::get('/users', [UserController::class, 'index']);
    Route::put('/users/{user}/role', [UserController::class, 'updateRole'])->middleware('role:admin,superadmin');
    Route::put('/users/{user}/status', [UserController::class, 'updateStatus']);
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->middleware('role:admin,superadmin');
    Route::post('/users/change-password', [UserController::class, 'changePassword']);
    Route::put('/users/update-profile', [UserController::class, 'updateProfile']);
    Route::get('/users/{user}/activity-log', [UserController::class, 'activityLog']);
    Route::post('/users/{user}/enable-2fa', [UserController::class, 'enable2FA']);
});

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

// Protected Routes (Requires JWT)
Route::middleware('auth:api')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/add-default-user', [AuthController::class, 'addDefaultUser']);
});

Route::get('/product/{id}', [ProductController::class, 'barcode']);


//for category
Route::get('/categories', [CategoryController::class, 'index']);       // Get all categories
Route::post('/categories', [CategoryController::class, 'store']);      // Create category
Route::get('/categories/{id}', [CategoryController::class, 'show']);   // Get single category
Route::put('/categories/{id}', [CategoryController::class, 'update']); // Update category
Route::delete('/categories/{id}', [CategoryController::class, 'destroy']); // Delete category

//for store location
Route::get('/store-locations', [StoreLocationController::class, 'index']);       // Get all store locations
Route::post('/store-locations', [StoreLocationController::class, 'store']);      // Create store location
Route::get('/store-locations/{id}', [StoreLocationController::class, 'show']);   // Get single store location
Route::put('/store-locations/{id}', [StoreLocationController::class, 'update']); // Update store location
Route::delete('/store-locations/{id}', [StoreLocationController::class, 'destroy']); // Delete store location

//for supplier
Route::get('/suppliers', [SupplierController::class, 'index']);       // Get all suppliers
Route::post('/suppliers', [SupplierController::class, 'store']);      // Create supplier
Route::get('/suppliers/{id}', [SupplierController::class, 'show']);   // Get single supplier
Route::put('/suppliers/{id}', [SupplierController::class, 'update']); // Update supplier
Route::delete('/suppliers/{id}', [SupplierController::class, 'destroy']); // Delete supplier

//for unit
Route::get('/units', [UnitController::class, 'index']);  // GET all units
Route::post('/units', [UnitController::class, 'store']);  // POST create a unit
Route::get('/units/{id}', [UnitController::class, 'show']);  // GET specific unit by ID
Route::put('/units/{id}', [UnitController::class, 'update']);  // PUT update a unit by ID
Route::delete('/units/{id}', [UnitController::class, 'destroy']);  // DELETE a unit by ID

//for product
Route::post('/products/delete/{product_name}', [ProductController::class, 'softDelete']); // Move item to deleted bin
Route::post('/products/restore/{product_name}', [ProductController::class, 'restore']); // Restore deleted item
Route::delete('/products/permanent-delete/{product_name}', [ProductController::class, 'permanentDelete']); // Permanently delete item
Route::get('/deleted-items', [ProductController::class, 'getDeletedItems']); // Fetch all deleted products (Admin only)
Route::get('/products', [ProductController::class, 'index']);
Route::post('/products', [ProductController::class, 'store']);
Route::get('/products/{id}', [ProductController::class, 'show']);
Route::put('/products/{id}', [ProductController::class, 'update']);
Route::delete('/products/{id}', [ProductController::class, 'destroy']);
Route::post('/products/import', [ProductController::class, 'import']);

//for customer
Route::get('/customers', [CustomerController::class, 'index']); // Get all customers
Route::post('/customers', [CustomerController::class, 'store']); // Create a new customer    
Route::put('/customers/{id}', [CustomerController::class, 'update']); // Update a customer
Route::delete('/customers/{id}', [CustomerController::class, 'destroy']); // Delete a customer

//for sale
Route::post('/sales', [SaleController::class, 'store']); // Create a new sale
Route::get('/sales', [SaleController::class, 'index']); // Get all sales
Route::put('/sales/{id}', [SaleController::class, 'update']); // Update a sale
Route::delete('/sales/{id}', [SaleController::class, 'destroy']); // Delete a sale
Route::get('/next-bill-number', [SaleController::class, 'getLastBillNumber']);
Route::get('/sales/daily-profit-report', [SaleController::class, 'getDailyProfitReport']);
Route::get('/sales/bill-wise-profit-report', [SaleController::class, 'getBillWiseProfitReport']);

//for stock report
Route::get('/stock-reports', [StockReportController::class, 'index']);
Route::get('/detailed-stock-reports', [StockReportController::class, 'detailedReport']);