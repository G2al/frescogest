<?php

use App\Http\Controllers\Admin\DeliveryDocumentController;
use Illuminate\Support\Facades\Route;

Route::get('/admin/orders/{order}/delivery-document', DeliveryDocumentController::class)
    ->middleware('auth')
    ->name('admin.orders.delivery-document');

Route::redirect('/', '/index.html');

require __DIR__.'/public-api.php';
