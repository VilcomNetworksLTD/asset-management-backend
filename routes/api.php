<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    AuthandAccessController,
    DashboardController,
    AssetController,
    AccessoryController,
    ConsumableController,
    ComponentController,
    LicenseController,
    MaintenanceController,
    ActivityLogController,
    ReportController,
    SettingController,
    TransferController,
    ReturnRequestController,
    TicketController,
    UserController // Added this for profile management
};

/* --- Public Routes --- */
Route::controller(AuthandAccessController::class)->group(function () {
    Route::post('/register', 'register');
    Route::post('/verify-otp', 'verifyOtp');
    Route::post('/login', 'login');
    Route::post('/resend-otp', 'resendOtp');
    Route::post('/forgot-password', 'forgotPassword');
    Route::post('/reset-password', 'resetPassword');
});

/* --- Protected Routes --- */
Route::middleware('auth:sanctum')->group(function () {

    Route::get('/user', function () {
        return response()->json(auth()->user());
    });
    Route::post('/logout', [AuthandAccessController::class, 'logout']);

    // --- Dashboard & Analytics ---
    Route::controller(DashboardController::class)->group(function () {
        Route::get('/stats', 'getStats');           // Admin stats
        Route::get('/user-stats', 'getUserAssets'); // Caleb's counts & recent assets
    });
    // Add this inside your Protected Routes (auth:sanctum)
    Route::get('/dashboard/admin', [DashboardController::class, 'index'])->name('admin.dashboard');

    // --- Inventory Management ---
    Route::get('/assets', [AssetController::class, 'index']);
    Route::get('/assets/list', [AssetController::class, 'list']);
    Route::post('/assets', [AssetController::class, 'store']);
    Route::put('/assets/{id}', [AssetController::class, 'update']);
    Route::delete('/assets/{id}', [AssetController::class, 'destroy']);

    Route::controller(AccessoryController::class)->group(function () {
        Route::get('/accessories', 'index');
        Route::get('/accessories/list', 'list');
        Route::post('/accessories', 'store');
        Route::put('/accessories/{id}', 'update');
        Route::delete('/accessories/{id}', 'destroy');
    });

    Route::controller(ConsumableController::class)->group(function () {
        Route::get('/consumables', 'index');
        Route::get('/consumables/list', 'list');
        Route::post('/consumables', 'store');
        Route::put('/consumables/{id}', 'update');
        Route::delete('/consumables/{id}', 'destroy');
    });

    Route::controller(LicenseController::class)->group(function () {
        Route::get('/licenses', 'index');
        Route::get('/licenses/list', 'list');
        Route::post('/licenses', 'store');
        Route::put('/licenses/{id}', 'update');
        Route::delete('/licenses/{id}', 'destroy');
    });

    Route::controller(MaintenanceController::class)->group(function () {
        Route::get('/maintenances', 'index');
        Route::get('/maintenances/list', 'list');
        Route::post('/maintenances', 'store');
        Route::put('/maintenances/{id}', 'update');
        Route::delete('/maintenances/{id}', 'destroy');
    });
    
    Route::controller(ComponentController::class)->group(function () {
        Route::get('/components', 'index');
        Route::get('/components/list', 'list');
        Route::post('/components', 'store');
        Route::put('/components/{id}', 'update');
        Route::delete('/components/{id}', 'destroy');
    });

    // --- Logistics & Transfers (The Redefined Gateway Workflow) ---
    Route::controller(TransferController::class)->group(function () {
        Route::get('/transfers', 'index');               // Admin View
        Route::post('/request-transfer', 'store');       // Staff request for transfer
        Route::get('/my-transfers', 'getUserTransfers'); // Caleb's transfer history
        Route::put('/transfers/{id}/status', 'updateStatus');
        Route::delete('/transfers/{id}', 'destroy');

        /* --- NEW: Admin-Gated Workflow Additions --- */
        
        // Staff Actions
        Route::get('/my-assets', 'getMyAssets');               // For return dropdown
        Route::post('/transfers/return', 'storeReturnRequest'); // Step 1: Initiate Return
        Route::get('/my-pending-assignments', 'getPendingAssignments'); // Step 3: Inbound notification
        Route::post('/assignments/{id}/verify', 'verifyInbound'); // Step 4: Digital Signature

        // Admin Actions
        Route::get('/admin/transfers/pending', 'indexPending'); // Inspection Queue
        Route::post('/admin/transfers/{id}/complete', 'completeInspection'); // Step 2: Physical Handover
        Route::post('/admin/assets/assign', 'assignToUser'); // New Assignment
    });

    // --- Dedicated Return Requests (Separate Table/Model/Controller/Service) ---
    Route::controller(ReturnRequestController::class)->group(function () {
        Route::get('/return-requests', 'index');
        Route::get('/my-return-requests', 'myRequests');
        Route::get('/my-returnable-assets', 'myAssets');
        Route::post('/return-requests', 'store');
        Route::put('/return-requests/{id}/status', 'updateStatus');
        Route::post('/admin/return-requests/{id}/complete', 'completeInspection');
        Route::delete('/return-requests/{id}', 'destroy');
    });

    // --- Support & Issue Tracking ---
    Route::controller(TicketController::class)->group(function () {
        Route::get('/tickets', 'index');             // Admin view
        Route::get('/tickets/list', 'list');
        Route::get('/my-tickets', 'getUserTickets'); // User view
        Route::get('/workflow/queues', 'getWorkflowQueues'); // Unified admin workflow queues
        Route::get('/workflow/my-assets', 'getMyReturnableAssets'); // Staff assets available for return
        Route::post('/tickets', 'store');            // Report an issue
        Route::post('/tickets/{id}/assign-asset', 'assignAsset'); // Admin assigns chosen asset to requester
        Route::post('/workflow/returns', 'createReturnRequest'); // Staff return-to-admin request
        Route::post('/workflow/returns/{id}/process', 'processReturn'); // Admin disposition: store or maintenance
        Route::put('/tickets/{id}', 'update');
        Route::delete('/tickets/{id}', 'destroy');
    });

    // --- Profile & Personal Settings ---
    Route::controller(UserController::class)->group(function () {
        Route::get('/users-list/paginated', 'list');
        Route::post('/users-list', 'store');
        Route::put('/users-list/{id}', 'updateById');
        Route::delete('/users-list/{id}', 'destroy');
        Route::get('/profile', 'show');               // Fetch Caleb's profile details
        Route::post('/profile/update', 'update');     // Update profile (name, avatar, etc.)
        Route::post('/profile/password', 'changePassword'); // Change password
        Route::get('/users-list', 'index');           // For "Transfer To" dropdown
    });

    // --- Logs, Reports & Global Settings ---
    Route::get('/activity-logs', [ActivityLogController::class, 'index']);
    
    Route::controller(ReportController::class)->group(function () {
        Route::get('/reports-summary', 'index');
        Route::get('/reports-history', 'history');
        Route::post('/reports/generate', 'generate');
        Route::get('/reports/{id}/download', 'download')->whereNumber('id');
    });

    Route::controller(SettingController::class)->group(function () {
        Route::get('/settings', 'index');           // Global settings
        Route::post('/settings', 'update');
    });
});