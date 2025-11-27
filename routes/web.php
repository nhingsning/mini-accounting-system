<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\CreditNoteController;
use App\Http\Controllers\ReceiptController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\QuotationController;
use App\Http\Controllers\CustomersController;   // <-- เพิ่มอันนี้ (พหูพจน์) สำหรับ API
use App\Http\Controllers\CustomerController;    // <-- อันนี้ (เอกพจน์) สำหรับ resource CRUD หน้าเว็บ
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\BankStatementController;
use App\Http\Controllers\SettingsController;

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

Route::post('/locale', [LocaleController::class, 'update'])->name('locale.update');

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

Route::get('/settings', [SettingsController::class, 'index'])
    ->middleware('auth')
    ->name('settings.index');
Route::post('/settings', [SettingsController::class, 'update'])
    ->middleware('auth')
    ->name('settings.update');
Route::get('/settings/layout', [SettingsController::class, 'layout'])
    ->middleware('auth')
    ->name('settings.layout');
Route::post('/settings/layout', [SettingsController::class, 'updateLayout'])
    ->middleware('auth')
    ->name('settings.layout.update');

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
Route::post('/invoices/{invoice}/restore', [InvoiceController::class, 'restore'])->name('invoices.restore');
Route::post('/invoices/{invoice}/submit-approval', [InvoiceController::class, 'submitForApproval'])
    ->name('invoices.submit-approval');
Route::post('/invoices/{invoice}/approve', [InvoiceController::class, 'approve'])
    ->name('invoices.approve');
Route::post('/invoices/{invoice}/reject', [InvoiceController::class, 'reject'])
    ->name('invoices.reject');

Route::post('/invoices/{invoice}/convert/receipt', [ReceiptController::class, 'fromInvoice'])
    ->name('invoices.convert.receipt');
Route::post('/invoices/{invoice}/convert/credit-note', [CreditNoteController::class, 'convertFromInvoice'])
    ->defaults('type', 'credit')
    ->name('invoices.convert.credit-note');
Route::post('/invoices/{invoice}/convert/debit-note', [CreditNoteController::class, 'convertFromInvoice'])
    ->defaults('type', 'debit')
    ->name('invoices.convert.debit-note');

Route::resource('po', PurchaseOrderController::class)
    ->only(['index','create','store','show','edit','update','destroy']);

Route::resource('quotations', QuotationController::class)
    ->only(['index','create','store','show','edit','update','destroy']);
Route::post('/quotations/{quotation}/restore', [QuotationController::class, 'restore'])->name('quotations.restore');
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
Route::post('/receipts/{receipt}/restore', [ReceiptController::class, 'restore'])->name('receipts.restore');
Route::get('/receipts/{receiptKey}/pdf', [ReceiptController::class, 'pdf'])->name('receipts.pdf');
Route::resource('debit-credit-note', CreditNoteController::class)
    ->only(['index','create','store','show','edit','update','destroy'])
    ->parameters(['debit-credit-note' => 'credit_note'])
    ->names('credit-notes');
Route::post('/debit-credit-note/{credit_note}/restore', [CreditNoteController::class, 'restore'])->name('credit-notes.restore');
Route::redirect('/credit-notes', '/debit-credit-note')->name('credit-notes.legacy');
Route::redirect('/credit-notes/create', '/debit-credit-note/create');
Route::redirect('/credit-notes/{credit_note}', '/debit-credit-note/{credit_note}');
Route::redirect('/credit-notes/{credit_note}/edit', '/debit-credit-note/{credit_note}/edit');
Route::post('/debit-credit-note/{credit_note}/submit-approval', [CreditNoteController::class, 'submitForApproval'])->name('credit-notes.submit-approval');
Route::post('/debit-credit-note/{credit_note}/approve', [CreditNoteController::class, 'approve'])->name('credit-notes.approve');
Route::post('/debit-credit-note/{credit_note}/reject', [CreditNoteController::class, 'reject'])->name('credit-notes.reject');
Route::resource('payments', PaymentController::class)->only(['index','store','destroy']);
Route::get('/bank-statements', [BankStatementController::class, 'index'])->name('bank-statements.index');
Route::post('/bank-statements/import', [BankStatementController::class, 'import'])->name('bank-statements.import');
Route::post('/bank-statements/reconcile', [BankStatementController::class, 'reconcile'])->name('bank-statements.reconcile');

Route::get('/invoices',                [InvoiceController::class, 'index'])->name('invoices.index');
Route::get('/invoices/{invoiceKey}',   [InvoiceController::class, 'show'])->name('invoices.show');
Route::get('/invoices/{invoiceKey}/pdf', [InvoiceController::class, 'pdf'])->name('invoices.pdf');
Route::get('/invoices/{invoiceKey}/edit', [InvoiceController::class, 'edit'])->name('invoices.edit');
Route::put('/invoices/{invoiceKey}',   [InvoiceController::class, 'update'])->name('invoices.update');
