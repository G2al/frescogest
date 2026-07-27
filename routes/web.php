<?php

use App\Http\Controllers\Admin\DeliveryDocumentController;
use App\Http\Controllers\Admin\DeliveryDocumentExportController;
use App\Http\Controllers\Admin\DeliveryDocumentFileController;
use App\Http\Controllers\Employees\EmployeeAttendanceController;
use App\Http\Controllers\Employees\EmployeeAuthController;
use App\Http\Controllers\StorefrontPageController;
use App\Services\Storefront\StoreOpeningHours;
use Illuminate\Support\Facades\Route;

Route::prefix('dipendenti')->name('employee.')->group(function (): void {
    Route::get('/', fn () => auth('employee')->check()
        ? redirect()->route('employee.attendance')
        : redirect()->route('employee.login'));
    Route::get('/accesso', [EmployeeAuthController::class, 'create'])->name('login');
    Route::post('/accesso', [EmployeeAuthController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('login.store');

    Route::middleware(['auth:employee', 'employee.active'])->group(function (): void {
        Route::get('/presenze', [EmployeeAttendanceController::class, 'index'])->name('attendance');
        Route::post('/presenze', [EmployeeAttendanceController::class, 'store'])->name('attendance.store');
        Route::post('/uscita', [EmployeeAuthController::class, 'destroy'])->name('logout');
    });
});

Route::get('/admin/orders/{order}/delivery-document', DeliveryDocumentController::class)
    ->middleware('auth:admin')
    ->name('admin.orders.delivery-document');

Route::get('/admin/delivery-documents/export', DeliveryDocumentExportController::class)
    ->middleware('auth:admin')
    ->name('admin.delivery-documents.export');

Route::get('/admin/delivery-documents/{deliveryDocument}', DeliveryDocumentFileController::class)
    ->middleware('auth:admin')
    ->name('admin.delivery-documents.show');

Route::get('/api/v1/store/status', fn (StoreOpeningHours $openingHours) => response()->json([
    'data' => $openingHours->status(),
]));

Route::middleware('store.open')->group(function (): void {
    Route::redirect('/', '/index.html');
    Route::redirect('/catalog.html', '/index.html');
    Route::get('/{page}', StorefrontPageController::class)
        ->where('page', 'cart\.html|forgot-password\.html|index\.html|login\.html|orders\.html|product\.html|profile\.html|register\.html|reset-password\.html|whatsapp\.html');
});

require __DIR__.'/public-api.php';
