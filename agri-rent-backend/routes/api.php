<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\RegisterController;

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

// Public routes (no authentication required)
Route::prefix('auth')->group(function () {
    // Registration routes
    Route::post('/register', [RegisterController::class, 'register']);
    Route::get('/registration-config', [RegisterController::class, 'getRegistrationConfig']);
    
    // Authentication routes
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/check', [AuthController::class, 'checkAuth']);
});

// Protected routes (authentication required)
Route::middleware('auth:sanctum')->group(function () {
    
    // User management routes
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/user', [AuthController::class, 'user']);
        Route::post('/switch-role', [AuthController::class, 'switchRole']);
        Route::get('/role-availability', [AuthController::class, 'checkRoleAvailability']);
    });
    
    // Profile management routes
    Route::prefix('profile')->group(function () {
        // Farmer profile routes
        Route::prefix('farmer')->group(function () {
            Route::get('/', [FarmerProfileController::class, 'show']);
            Route::put('/', [FarmerProfileController::class, 'update']);
            Route::post('/create', [FarmerProfileController::class, 'create']);
        });
        
        // Owner profile routes  
        Route::prefix('owner')->group(function () {
            Route::get('/', [OwnerProfileController::class, 'show']);
            Route::put('/', [OwnerProfileController::class, 'update']);
            Route::post('/create', [OwnerProfileController::class, 'create']);
            Route::put('/bank-details', [OwnerProfileController::class, 'updateBankDetails']);
        });
    });
    
    // Equipment routes (for owners)
    Route::prefix('equipment')->middleware('role:owner')->group(function () {
        Route::get('/', [EquipmentController::class, 'index']);
        Route::post('/', [EquipmentController::class, 'store']);
        Route::get('/{equipment}', [EquipmentController::class, 'show']);
        Route::put('/{equipment}', [EquipmentController::class, 'update']);
        Route::delete('/{equipment}', [EquipmentController::class, 'destroy']);
        Route::patch('/{equipment}/toggle-availability', [EquipmentController::class, 'toggleAvailability']);
    });
    
    // Booking routes
    Route::prefix('bookings')->group(function () {
        // Farmer routes (create bookings)
        Route::middleware('role:farmer')->group(function () {
            Route::post('/', [BookingController::class, 'store']);
            Route::get('/my-bookings', [BookingController::class, 'farmerBookings']);
        });
        
        // Owner routes (manage bookings)
        Route::middleware('role:owner')->group(function () {
            Route::get('/received', [BookingController::class, 'ownerBookings']);
            Route::patch('/{booking}/accept', [BookingController::class, 'acceptBooking']);
            Route::patch('/{booking}/reject', [BookingController::class, 'rejectBooking']);
            Route::patch('/{booking}/complete', [BookingController::class, 'completeBooking']);
        });
        
        // Common booking routes
        Route::get('/{booking}', [BookingController::class, 'show']);
        Route::patch('/{booking}/cancel', [BookingController::class, 'cancelBooking']);
    });
    
    // Search routes
    Route::prefix('search')->group(function () {
        Route::get('/equipment', [SearchController::class, 'searchEquipment']);
        Route::get('/owners', [SearchController::class, 'searchOwners']);
        Route::get('/farmers', [SearchController::class, 'searchFarmers']);
    });
    
    // Notification routes
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::patch('/{notification}/mark-read', [NotificationController::class, 'markAsRead']);
        Route::patch('/mark-all-read', [NotificationController::class, 'markAllAsRead']);
        Route::delete('/{notification}', [NotificationController::class, 'destroy']);
    });
    
    // General routes
    Route::prefix('general')->group(function () {
        Route::get('/districts', [GeneralController::class, 'getDistricts']);
        Route::get('/equipment-types', [GeneralController::class, 'getEquipmentTypes']);
        Route::get('/crop-types', [GeneralController::class, 'getCropTypes']);
        Route::get('/livestock-types', [GeneralController::class, 'getLivestockTypes']);
    });
});

// Fallback route for API
Route::fallback(function () {
    return response()->json([
        'success' => false,
        'message' => 'API endpoint not found.',
    ], 404);
}); 