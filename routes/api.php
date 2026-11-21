<?php

use App\Http\Controllers\Api\PortalApiController;
use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => response()->json(['ok' => true, 'app' => 'PetrolTrack']));
Route::post('/login', [PortalApiController::class, 'login']);
Route::post('/register', [PortalApiController::class, 'register']);
Route::get('/portal-state', [PortalApiController::class, 'portalState']);

Route::middleware('auth.api_token')->group(function () {
Route::post('/shifts/start', [PortalApiController::class, 'startShift']);
Route::post('/shifts/end', [PortalApiController::class, 'endShift']);
Route::post('/sales', [PortalApiController::class, 'storeSale']);
Route::post('/sales/{sale}/void-request', [PortalApiController::class, 'requestVoid']);
Route::post('/borrowers', [PortalApiController::class, 'storeBorrower']);
Route::delete('/borrowers/{borrower}', [PortalApiController::class, 'destroyBorrower']);
Route::post('/loans/{loan}/pay', [PortalApiController::class, 'recordPayment']);

Route::prefix('owner')->group(function () {
    Route::post('/voids/{voidRequest}/approve', [PortalApiController::class, 'approveVoid']);
    Route::post('/voids/{voidRequest}/reject', [PortalApiController::class, 'rejectVoid']);
    Route::post('/prices', [PortalApiController::class, 'updatePrice']);
    Route::post('/deliveries', [PortalApiController::class, 'storeDelivery']);
    Route::post('/accounts/{user}/approve', [PortalApiController::class, 'approveAccount']);
    Route::delete('/accounts/{user}/reject', [PortalApiController::class, 'rejectAccount']);
});
});
