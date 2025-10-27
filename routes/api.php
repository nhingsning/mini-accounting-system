<?php

use Illuminate\Support\Facades\Route;

Route::get('/ping', fn () => ['ok' => true, 'time' => now()]);
use App\Http\Controllers\InvoiceController;
Route::get('/invoices', [InvoiceController::class,'index']);
Route::get('/invoices/{invoice}', [InvoiceController::class,'show']);
Route::get('/invoices/{key}', [InvoiceController::class, 'show']);