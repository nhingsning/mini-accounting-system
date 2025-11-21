<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\ReceiptController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\QuotationController;
use App\Http\Controllers\CustomersController;   // <-- เพิ่มอันนี้ (พหูพจน์) สำหรับ API
use App\Http\Controllers\CustomerController;    // <-- อันนี้ (เอกพจน์) สำหรับ resource CRUD หน้าเว็บ

/*
|--------------------------------------------------------------------------
| API: Customers (ใช้กับ Quotation/Invoice dropdown + ดึงรายละเอียด)
|--------------------------------------------------------------------------
*/
Route::prefix('api')->group(function () {
    Route::get('/customers/options', [CustomersController::class, 'options'])
        ->name('customers.options');
    Route::get('/customers/{customer}.json', [CustomersController::class, 'showJson'])
        ->name('customers.json');
});

/*
|--------------------------------------------------------------------------
| Auth
|--------------------------------------------------------------------------
*/
Route::get('/auth', [AuthController::class, 'show'])
    ->name('auth.page')
    ->middleware('guest');

Route::get('/login', fn () => redirect()->route('auth.page'))
    ->name('login')
    ->middleware('guest');

Route::post('/login', [AuthController::class, 'login'])
    ->name('login.attempt')
    ->middleware('guest');

Route::get('/register', [AuthController::class, 'registerForm'])
    ->name('register.form')
    ->middleware('guest');

Route::post('/register', [AuthController::class, 'register'])
    ->name('register.store')
    ->middleware('guest');

Route::post('/logout', [AuthController::class, 'logout'])
    ->name('logout')
    ->middleware('auth');

/*
|--------------------------------------------------------------------------
| Dashboard (ต้องล็อกอิน)
|--------------------------------------------------------------------------
*/
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware('auth')
    ->name('dashboard');

/*
|--------------------------------------------------------------------------
| Home
|--------------------------------------------------------------------------
*/
Route::get('/', fn () => redirect()->route('invoices.index'))
    ->name('home');

/*
|--------------------------------------------------------------------------
| Quotations / Invoices / Customers (หน้าเว็บ)
|--------------------------------------------------------------------------
*/
Route::patch('/quotations/{quotation}/autosave', [QuotationController::class, 'autosave'])
    ->name('quotations.autosave');
Route::get('/quotations/{quotation}/pdf', [QuotationController::class, 'pdf'])
    ->name('quotations.pdf');

Route::resource('invoices', InvoiceController::class)
    ->only(['index','create','store','show','edit','update','destroy']);

Route::post('/invoices/{invoice}/convert/receipt', [ReceiptController::class, 'fromInvoice'])
    ->name('invoices.convert.receipt');

Route::resource('po', PurchaseOrderController::class)
    ->only(['index','create','store','show','edit','update','destroy']);

Route::resource('quotations', QuotationController::class)
    ->only(['index','create','store','show','edit','update','destroy']);
Route::post('/quotations/{quotation}/copy', [QuotationController::class, 'copy'])
    ->name('quotations.copy');
Route::post('/quotations/{quotation}/convert/invoice', [QuotationController::class,'convertToInvoice'])->name('quotations.convert.invoice');
Route::post('/quotations/{quotation}/convert/po', [QuotationController::class,'convertToPo'])->name('quotations.convert.po');

Route::resource('customers', CustomerController::class); // /customers, /customers/create, /customers/{id}/edit

/*
|--------------------------------------------------------------------------
| Legacy redirects (quotes → quotations)
|--------------------------------------------------------------------------
*/
Route::redirect('/quotes', '/quotations');
Route::redirect('/quotes/create', '/quotations/create');
Route::redirect('/quotes/{quotation}', '/quotations/{quotation}');
Route::redirect('/quotes/{quotation}/edit', '/quotations/{quotation}/edit');
Route::redirect('/quotes/{quotation}/pdf', '/quotations/{quotation}/pdf')->name('quotes.pdf');
Route::redirect('/quotes/{quotation}/send', '/quotations/{quotation}/send')->name('quotes.send');
Route::redirect('/quotes/{quotation}/convert', '/quotations/{quotation}/convert')->name('quotes.convert');

Route::resource('receipts', ReceiptController::class)->only(['index','create','store','show','edit','update','destroy']);

Route::get('/invoices',                [InvoiceController::class, 'index'])->name('invoices.index');
Route::get('/invoices/{invoiceKey}',   [InvoiceController::class, 'show'])->name('invoices.show');
Route::get('/invoices/{invoiceKey}/edit', [InvoiceController::class, 'edit'])->name('invoices.edit');
Route::put('/invoices/{invoiceKey}',   [InvoiceController::class, 'update'])->name('invoices.update');
