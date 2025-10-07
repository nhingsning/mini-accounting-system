<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InvoiceController;

Route::redirect('/', '/invoices');
Route::resource('invoices', InvoiceController::class);
