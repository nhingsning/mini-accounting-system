<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InvoiceController;

Route::get('/', fn () => redirect()->route('invoices.index'));
Route::resource('invoices', InvoiceController::class);

