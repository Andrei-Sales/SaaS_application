<?php

use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

// Routes that require authentication, email verification, and tenant access
Route::middleware(['auth', 'verified', 'tenant'])->group(function () {
    // Dashboard
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    // Invoice Routes
    Route::resource('invoices', InvoiceController::class);

    // Additional Invoice Actions
    Route::post('/invoices/{invoice}/mark-as-sent', [InvoiceController::class, 'markAsSent'])
        ->name('invoices.mark-as-sent');
    Route::post('/invoices/{invoice}/mark-as-paid', [InvoiceController::class, 'markAsPaid'])
        ->name('invoices.mark-as-paid');
    Route::get('/invoices/{invoice}/pdf', [InvoiceController::class, 'downloadPdf'])
        ->name('invoices.pdf');
    Route::post('/invoices/{invoice}/email', [InvoiceController::class, 'sendEmail'])
        ->name('invoices.email');

    // Subscription Routes
    Route::get('/subscriptions', [\App\Http\Controllers\SubscriptionController::class, 'index'])
        ->name('subscriptions.index');
    Route::get('/subscriptions/upgrade', [\App\Http\Controllers\SubscriptionController::class, 'showUpgrade'])
        ->name('subscriptions.upgrade');
    Route::post('/subscriptions/upgrade', [\App\Http\Controllers\SubscriptionController::class, 'upgrade']);
    Route::post('/subscriptions/cancel', [\App\Http\Controllers\SubscriptionController::class, 'cancel'])
        ->name('subscriptions.cancel');
    Route::post('/subscriptions/resume', [\App\Http\Controllers\SubscriptionController::class, 'resume'])
        ->name('subscriptions.resume');
});

// Auth routes will be added by Breeze
require __DIR__.'/auth.php';
