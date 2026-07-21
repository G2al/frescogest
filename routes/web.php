<?php

use App\Http\Controllers\Admin\DeliveryDocumentController;
use App\Http\Controllers\Admin\DeliveryDocumentExportController;
use Illuminate\Support\Facades\Route;

Route::get('/admin/orders/{order}/delivery-document', DeliveryDocumentController::class)
    ->middleware('auth:admin')
    ->name('admin.orders.delivery-document');

Route::get('/admin/delivery-documents/export', DeliveryDocumentExportController::class)
    ->middleware('auth:admin')
    ->name('admin.delivery-documents.export');

Route::redirect('/', '/index.html');
Route::redirect('/catalog.html', '/index.html');

require __DIR__.'/public-api.php';
