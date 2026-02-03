<?php

use App\Http\Controllers\ProfileController;
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
    // Redirect authenticated users to dashboard, others see landing page
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    return view('landing');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Invoice and Subscription Routes (require auth + tenant middleware)
Route::middleware(['auth', 'verified', 'tenant'])->group(function () {
    // Invoice Routes
    Route::resource('invoices', \App\Http\Controllers\InvoiceController::class);

    // Additional Invoice Actions
    Route::post('/invoices/{invoice}/mark-as-sent', [\App\Http\Controllers\InvoiceController::class, 'markAsSent'])
        ->name('invoices.mark-as-sent');
    Route::post('/invoices/{invoice}/mark-as-paid', [\App\Http\Controllers\InvoiceController::class, 'markAsPaid'])
        ->name('invoices.mark-as-paid');
    Route::get('/invoices/{invoice}/pdf', [\App\Http\Controllers\InvoiceController::class, 'downloadPdf'])
        ->name('invoices.pdf');
    Route::post('/invoices/{invoice}/email', [\App\Http\Controllers\InvoiceController::class, 'sendEmail'])
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

require __DIR__.'/auth.php';
