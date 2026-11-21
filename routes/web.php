<?php

use App\Http\Controllers\AccountApprovalController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\FuelInventoryController;
use App\Http\Controllers\FuelSaleController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ShiftController;
use App\Http\Controllers\VoidRequestController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/', function () {
        $user = auth()->user();
        $activeShift = $user->activeShift;
        $pendingCount = $user->isOwner() ? \App\Models\User::where('status', 'Pending')->count() : 0;
        $pendingVoidsCount = $user->isOwner() ? \App\Models\VoidRequest::where('status', 'Pending')->count() : 0;
        $lowStockGrades = \App\Models\FuelGrade::with('stock')
            ->get()
            ->filter(fn ($g) => $g->stock && (float) $g->stock->liters_on_hand <= (float) $g->stock->low_stock_threshold);

        return view('dashboard', compact('user', 'activeShift', 'pendingCount', 'pendingVoidsCount', 'lowStockGrades'));
    })->name('dashboard');

    // Shift lifecycle
    Route::post('/shifts/start', [ShiftController::class, 'start'])->name('shifts.start');
    Route::post('/shifts/end', [ShiftController::class, 'end'])->name('shifts.end');

    // Sales logbook & void requests
    Route::get('/sales', [FuelSaleController::class, 'index'])->name('sales.index');
    Route::post('/sales', [FuelSaleController::class, 'store'])->name('sales.store');
    Route::post('/sales/{sale}/void-requests', [VoidRequestController::class, 'store'])->name('sales.void-requests.store');

    // Borrowers & loans
    Route::get('/loans', [LoanController::class, 'index'])->name('loans.index');
    Route::post('/borrowers', [LoanController::class, 'storeBorrower'])->name('borrowers.store');
    Route::post('/loans/{loan}/payments', [LoanController::class, 'recordPayment'])->name('loans.payments.store');

    // Owner-only routes
    Route::middleware('role:Owner')->group(function () {
        Route::get('/owner/accounts', [AccountApprovalController::class, 'index'])->name('owner.accounts');
        Route::patch('/owner/accounts/{user}/approve', [AccountApprovalController::class, 'approve'])->name('owner.accounts.approve');
        Route::delete('/owner/accounts/{user}/reject', [AccountApprovalController::class, 'reject'])->name('owner.accounts.reject');

        Route::get('/owner/inventory', [FuelInventoryController::class, 'index'])->name('owner.inventory');
        Route::post('/owner/inventory/prices', [FuelInventoryController::class, 'updatePrice'])->name('owner.inventory.prices.store');
        Route::post('/owner/inventory/deliveries', [FuelInventoryController::class, 'storeDelivery'])->name('owner.inventory.deliveries.store');

        Route::get('/owner/voids', [VoidRequestController::class, 'index'])->name('owner.voids');
        Route::patch('/owner/voids/{voidRequest}/approve', [VoidRequestController::class, 'approve'])->name('owner.voids.approve');
        Route::patch('/owner/voids/{voidRequest}/reject', [VoidRequestController::class, 'reject'])->name('owner.voids.reject');

        Route::get('/owner/reports', [ReportController::class, 'index'])->name('owner.reports');
        Route::get('/owner/audit-logs', [AuditLogController::class, 'index'])->name('owner.audit-logs');
    });
});