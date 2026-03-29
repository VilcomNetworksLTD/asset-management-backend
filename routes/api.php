<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    AuthandAccessController,
    DashboardController,
    AssetController,
    AccessoryController,
    ConsumableController,
    ComponentController,
    FeedbackController,
    LicenseController,
    MaintenanceController,
    ActivityLogController,
    ReportController,
    SettingController,
    TransferController,
    ReturnRequestController,
    TicketController,
    UserController,
    SslCertificateController,
    SupplierController,
    StatusController,
    UserHistoryController,
    DepartmentController,
    CategoryController, 
    LocationController,
    BarcodeController
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

Route::get('/barcodes/{asset_id}/image', [AssetController::class, 'showBarcodeImage'])
    ->where('asset_id', '.*');

 //Route::get('/barcodes/{id}/image', [BarcodeController::class, 'image']);

/* --- Protected Routes --- */
Route::middleware(['auth:sanctum','maintenance'])->group(function () {

    Route::get('/user/history', [UserHistoryController::class, 'index']);

    Route::get('/user', function () {
        return response()->json(auth()->user());
    });

    Route::post('/logout', [AuthandAccessController::class, 'logout']);

    // --- Dashboard & Analytics ---
    Route::controller(DashboardController::class)->group(function () {
        Route::get('/stats', 'getStats');
        Route::get('/user-stats', 'getUserAssets');
    });
    Route::get('/dashboard/admin', [DashboardController::class, 'index'])->name('admin.dashboard');

    // --- Inventory Management (Assets) ---
    Route::controller(AssetController::class)->group(function () {
        Route::get('/assets', 'index');
        Route::get('/assets/list', 'list');
        Route::get('/hod/department-assets', 'hodDepartmentAssets');
        Route::post('/assets', 'store');
        Route::get('/assets/{id}', 'show');
        Route::put('/assets/{id}', 'update');
        Route::delete('/assets/{id}', 'destroy');
        Route::post('/assets/{id}/assign', 'assign');
        Route::get('/activity-logs/{id}', 'fetchPreAssets');
        
        // === NEW TONER LIFECYCLE ROUTES ===
        Route::post('/assets/{id}/replace-toner', 'replaceToner');
        Route::get('/assets/{id}/toner-history', 'getTonerHistory');

        // === BARCODE FUNCTIONALITY ===
        // Route for the scanner to find asset details by scanning the VNL code
        Route::get('/barcodes/{barcode_content}/details', 'findAssetByBarcode')
            ->where('barcode_content', '.*');
            
        Route::post('/assets/{id}/evidence', 'uploadEvidence');

    });
    // --- Categories & Locations (NEW DYNAMIC ENDPOINTS) ---
    Route::controller(CategoryController::class)->group(function () {
        Route::get('/categories', 'index');
        Route::post('/categories', 'store');
        Route::put('/categories/{category}', 'update');
        Route::delete('/categories/{category}', 'destroy');
    });

    Route::controller(LocationController::class)->group(function () {
        Route::get('/locations', 'index');
        Route::post('/locations', 'store');
        Route::put('/locations/{id}', 'update');
        Route::delete('/locations/{id}', 'destroy');
    });

    // --- Supplier Management ---
    Route::controller(SupplierController::class)->group(function () {
        Route::get('/suppliers/list', 'list');
        Route::post('/suppliers', 'store');
        Route::get('/suppliers/{id}', 'show');
        Route::put('/suppliers/{id}', 'update');
        Route::delete('/suppliers/{id}', 'destroy');
    });

    // --- Accessories ---
    Route::controller(AccessoryController::class)->group(function () {
        Route::get('/accessories', 'index');
        Route::get('/accessories/list', 'list');
        Route::post('/accessories', 'store');
        Route::put('/accessories/{id}', 'update');
        Route::delete('/accessories/{id}', 'destroy');
        Route::post('/accessories/{id}/assign', 'assign');
        Route::get('/my-accessories', 'myAccessories');
    });

    // --- Consumables ---
    Route::controller(ConsumableController::class)->group(function () {
        Route::get('/consumables', 'index');
        Route::get('/consumables/list', 'list');
        Route::get('/consumables/history', 'usageHistory'); 
        Route::get('/consumables/low-stock', 'lowStock');
        Route::get('/consumables/usage-report', 'usageReport');
        Route::post('/consumables', 'store');
        Route::put('/consumables/{id}', 'update');
        Route::delete('/consumables/{id}', 'destroy');
        Route::post('/consumables/{id}/assign', 'assign');
        Route::get('/my-consumables', 'myConsumables');
    });

    // --- Licenses ---
    Route::controller(LicenseController::class)->group(function () {
        Route::get('/licenses', 'index');
        Route::get('/licenses/list', 'list');
        Route::post('/licenses', 'store');
        Route::put('/licenses/{id}', 'update');
        Route::delete('/licenses/{id}', 'destroy');
        Route::post('/licenses/{id}/assign', 'assign');
        Route::get('/my-licenses', 'myLicenses');
    });

    // --- Maintenances ---
    Route::controller(MaintenanceController::class)->group(function () {
        Route::get('/maintenances', 'index');
        Route::get('/maintenances/list', 'list');
        Route::post('/maintenances', 'store');
        Route::put('/maintenances/{id}', 'update');
        Route::post('/maintenances/{id}/archive', 'archive');
        Route::delete('/maintenances/{id}', 'destroy');
    });
    
    // --- Components ---
    Route::controller(ComponentController::class)->group(function () {
        Route::get('/components', 'index');
        Route::get('/components/list', 'list');
        Route::post('/components', 'store');
        Route::put('/components/{id}', 'update');
        Route::delete('/components/{id}', 'destroy');
        Route::post('/components/{id}/assign', 'assign');
        Route::get('/my-components', 'myComponents');
    });

    // --- Logistics & Transfers ---
    Route::controller(TransferController::class)->group(function () {
        Route::get('/transfers', 'index');             
        Route::post('/request-transfer', 'store');       
        Route::get('/my-transfers', 'getUserTransfers');
        Route::put('/transfers/{id}/status', 'updateStatus');
        Route::delete('/transfers/{id}', 'destroy');
        Route::get('/my-assets', 'getMyAssets');               
        Route::post('/transfers/return', 'storeReturnRequest'); 
        Route::get('/my-pending-assignments', 'getPendingAssignments');
        Route::post('/assignments/{id}/verify', 'verifyInbound'); 

        // Admin Actions
        Route::get('/admin/transfers/pending', 'indexPending');
        Route::post('/admin/transfers/{id}/complete', 'completeInspection'); 
        Route::post('/admin/assets/assign', 'assignToUser');
    });

    // --- Return Requests ---
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
        Route::get('/tickets', 'index');            
        Route::get('/tickets/list', 'list');
        Route::get('/my-tickets', 'getUserTickets'); 
        Route::get('/workflow/queues', 'getWorkflowQueues'); 
        Route::get('/workflow/my-assets', [ReturnRequestController::class, 'myAssets']); 
        Route::post('/tickets', 'store');            
        Route::post('/tickets/{id}/assign-asset', 'assignAsset'); 
        Route::post('/tickets/{id}/escalate', 'escalateToPurchase');
        Route::post('/workflow/returns', 'createReturnRequest'); 
        Route::post('/workflow/returns/{id}/process', 'processReturn');
        Route::put('/tickets/{id}', 'update');
        Route::delete('/tickets/{id}', 'destroy');
    });

    // --- Feedback ---
    Route::apiResource('feedback', FeedbackController::class)->except(['show']);

    // --- Profile & User Management ---
    Route::controller(UserController::class)->group(function () {
        Route::get('/users/{id}', 'getUserDetails'); 
        Route::get('/users-list/paginated', 'list');
        Route::post('/users-list', 'store');
        Route::put('/users-list/{id}', 'updateById');
        Route::delete('/users-list/{id}', 'destroy');
        Route::get('/profile', 'show');               
        Route::post('/profile/update', 'update');     
        Route::post('/profile/password', 'changePassword'); 
        Route::get('/users-list', 'index');           
        Route::get('/my-assigned-items', 'getMyAssignedItems');
    });

    // --- Logs, Reports & Global Settings ---
    Route::get('/activity-logs', [ActivityLogController::class, 'index']);
    Route::get('/statuses', [StatusController::class, 'index']);
    Route::get('/suppliers', [SupplierController::class, 'index']);
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/departments', [DepartmentController::class, 'index']);

    Route::controller(ReportController::class)->group(function () {
        Route::get('/reports-summary', 'index');
        Route::get('/reports-history', 'history');
        Route::post('/reports/generate', 'generate');
        Route::get('/reports/{id}/download', 'download')->whereNumber('id');
    });

    Route::controller(SettingController::class)->group(function () {
        Route::get('/settings', 'index');           
        Route::post('/settings', 'update')->middleware('role:admin');
        Route::get('/admin/settings', 'index');
        Route::post('/admin/settings', 'update')->middleware('role:admin');
    });

    // --- Admin Only SSL Management ---
    Route::group(['middleware' => ['auth:sanctum', 'role:admin']], function () {
        Route::controller(SslCertificateController::class)->group(function () {
            Route::get('/ssl-certificates', 'index');
            Route::get('/ssl-certificates/list', 'list');
            Route::get('/ssl-certificates/all', 'all');
            Route::post('/ssl-certificates', 'store');
            Route::put('/ssl-certificates/{id}', 'update');
            Route::delete('/ssl-certificates/{id}', 'destroy');
            Route::post('/ssl-certificates/{id}/scan', 'scan');
            Route::post('/ssl-certificates/scan-domain', 'scanDomain');
            Route::post('/ssl-certificates/{id}/check-revocation', 'checkRevocation');
            Route::post('/ssl-certificates/{id}/acknowledge', 'acknowledge');
            Route::get('/ssl-certificates/{id}/change-logs', 'changeLogs');
        });
    });

    // --- Purchase Requests (Management Workflow) ---
    Route::controller(\App\Http\Controllers\PurchaseController::class)->group(function () {
        Route::get('/purchase-requests', 'index');
        Route::post('/purchase-requests', 'store');
        Route::post('/purchase-requests/maintenance-escalate', 'escalateFromMaintenance');
        Route::post('/purchase-requests/{id}/approve', 'approve');
        Route::post('/purchase-requests/{id}/reject', 'reject');
        Route::put('/purchase-requests/{id}/status', 'updateStatus');
        Route::post('/purchase-requests/{id}/mark-purchased', 'markAsPurchased');
        Route::get('/admin/purchase-requests', 'adminIndex');
        Route::post('/purchase-requests/{id}/escalate', 'escalateToManagement');
    });
});
