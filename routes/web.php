<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\QuotationController;

Route::get('/', fn() => redirect()->route('invoices.index'));

Route::resource('invoices',    InvoiceController::class)->except(['destroy']);

// Quotation (QT)
Route::resource('quotes',      QuotationController::class)->except(['destroy']);
// ชื่อเส้นทางจะเป็น quotes.index, quotes.create, quotes.store, ...
