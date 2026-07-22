<?php

use App\Http\Controllers\Admin\DeliveryDocumentController;
use App\Http\Controllers\Admin\DeliveryDocumentExportController;
use App\Http\Controllers\StorefrontPageController;
use App\Services\Storefront\StoreOpeningHours;
use Illuminate\Support\Facades\Route;

Route::get('/admin/orders/{order}/delivery-document', DeliveryDocumentController::class)
    ->middleware('auth:admin')
    ->name('admin.orders.delivery-document');

Route::get('/admin/delivery-documents/export', DeliveryDocumentExportController::class)
    ->middleware('auth:admin')
    ->name('admin.delivery-documents.export');

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
