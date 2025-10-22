<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\QuotationController;
use App\Http\Controllers\CustomerController;


// API เลือกลูกค้า/ดึงรายละเอียด (ใช้กับ Quotation/Invoice)
Route::get('/api/customers/options', [CustomersController::class, 'options'])
    ->name('customers.options');
Route::get('/api/customers/{customer}.json', [CustomersController::class, 'showJson'])
    ->name('customers.json');
// routes/web.php

Route::get('/api/customers/options', [CustomersController::class,'options'])->name('customers.options');      // list สำหรับ dropdown
Route::get('/api/customers/{customer}.json', [CustomersController::class,'showJson'])->name('customers.json'); // รายละเอียด 1 ราย

Route::resource('customers', CustomerController::class); // /customers, /customers/create, /customers/{id}/edit


// หน้า login หลักของเราอยู่ที่ /auth
Route::get('/auth', [AuthController::class, 'show'])
    ->name('auth.page')
    ->middleware('guest');

// ทำ /login (GET) เป็น alias ที่ชื่อ 'login' → redirect ไป /auth
Route::get('/login', fn () => redirect()->route('auth.page'))
    ->name('login')            // <<< สำคัญ: ให้ชื่อว่า login
    ->middleware('guest');

// POST login
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
| Home → ไป Invoices (เลือกตัวเดียวพอ)
|--------------------------------------------------------------------------
*/
Route::get('/', fn () => redirect()->route('invoices.index'))
    ->name('home');

/*
|--------------------------------------------------------------------------
| Quotations / Invoices
|--------------------------------------------------------------------------
*/
Route::patch('/quotations/{quotation}/autosave', [QuotationController::class, 'autosave'])
    ->name('quotations.autosave'); // ใช้ {quotation} ให้ตรงกับ model binding

Route::resource('invoices', InvoiceController::class);

Route::resource('quotations', QuotationController::class)
    ->only(['index','create','store','show','edit','update','destroy']);

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
