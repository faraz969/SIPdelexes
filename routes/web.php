<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\DashboardController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return redirect()->route('login');
});

Auth::routes();

Route::middleware(['auth', 'staff'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/applications/{id}', [App\Http\Controllers\AdminController::class, 'show'])->name('applications.show');
    Route::post('/applications/{id}/status', [App\Http\Controllers\AdminController::class, 'updateStatus'])->name('applications.updateStatus');
    Route::delete('/applications/{id}', [App\Http\Controllers\AdminController::class, 'destroy'])->name('applications.destroy');
    
    // Department Management
    Route::resource('departments', App\Http\Controllers\Admin\DepartmentController::class);
    
    // Program Management
    Route::resource('programs', App\Http\Controllers\Admin\ProgramController::class);
    
    // User Management
    Route::resource('users', App\Http\Controllers\Admin\UserController::class);
    Route::post('/users/{user}/reset-password', [App\Http\Controllers\Admin\UserController::class, 'resetPassword'])->name('users.resetPassword');
    
    // Form Type Management
    Route::resource('form-types', App\Http\Controllers\Admin\FormTypeController::class);
    
    // ERP Integration Management
    Route::prefix('erp')->name('erp.')->group(function () {
        Route::get('/dashboard', [App\Http\Controllers\Admin\ERPController::class, 'dashboard'])->name('dashboard');
        Route::get('/students', [App\Http\Controllers\Admin\ERPController::class, 'students'])->name('students');
        Route::get('/students/{student}', [App\Http\Controllers\Admin\ERPController::class, 'showStudent'])->name('students.show');
        Route::get('/invoices', [App\Http\Controllers\Admin\ERPController::class, 'invoices'])->name('invoices');
        Route::get('/invoices/generate', [App\Http\Controllers\Admin\ERPController::class, 'showGenerateInvoiceForm'])->name('invoices.generate-form');
        Route::post('/invoices/generate', [App\Http\Controllers\Admin\ERPController::class, 'generateInvoice'])->name('invoices.generate');
        Route::get('/invoices/sync', [App\Http\Controllers\Admin\ERPController::class, 'showSyncInvoiceForm'])->name('invoices.sync-form');
        Route::post('/invoices/sync', [App\Http\Controllers\Admin\ERPController::class, 'syncInvoice'])->name('invoices.sync');
        Route::get('/invoices/{invoice}', [App\Http\Controllers\Admin\ERPController::class, 'showInvoice'])->name('invoices.show');
        Route::get('/payments', [App\Http\Controllers\Admin\ERPController::class, 'payments'])->name('payments');
        Route::post('/payments/{payment}/process', [App\Http\Controllers\Admin\ERPController::class, 'processPayment'])->name('payments.process');
        Route::get('/activity-logs', [App\Http\Controllers\Admin\ActivityLogController::class, 'index'])->name('activity-logs');
        Route::get('/activity-logs/{activityLog}', [App\Http\Controllers\Admin\ActivityLogController::class, 'show'])->name('activity-logs.show');
    });
    
    // Registration Rules Management
    Route::resource('registration-rules', App\Http\Controllers\Admin\RegistrationRuleController::class);
    
    // Sessions, Campuses, and Intakes Management
    Route::resource('sessions', App\Http\Controllers\Admin\SessionController::class);
    Route::resource('campuses', App\Http\Controllers\Admin\CampusController::class);
    Route::resource('intakes', App\Http\Controllers\Admin\IntakeController::class);
});

// HOD Routes
Route::middleware(['auth', 'role:hod'])->prefix('hod')->name('hod.')->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\HODController::class, 'dashboard'])->name('dashboard');
    Route::get('/applications/{application}', [App\Http\Controllers\HODController::class, 'showApplication'])->name('applications.show');
    Route::post('/applications/{application}/approve', [App\Http\Controllers\HODController::class, 'approveApplication'])->name('applications.approve');
    Route::post('/applications/{application}/reject', [App\Http\Controllers\HODController::class, 'rejectApplication'])->name('applications.reject');
});

// President Routes
Route::middleware(['auth', 'role:president'])->prefix('president')->name('president.')->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\PresidentController::class, 'dashboard'])->name('dashboard');
    Route::get('/applications/{application}', [App\Http\Controllers\PresidentController::class, 'showApplication'])->name('applications.show');
    // Comment-only endpoint (status updates disabled)
    Route::post('/applications/{application}/comment', [App\Http\Controllers\PresidentController::class, 'commentApplication'])->name('applications.comment');
});

// Registrar Routes
Route::middleware(['auth', 'role:registrar'])->prefix('registrar')->name('registrar.')->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\RegistrarController::class, 'dashboard'])->name('dashboard');
    Route::get('/applications/{application}', [App\Http\Controllers\RegistrarController::class, 'showApplication'])->name('applications.show');
    Route::post('/applications/{application}/approve', [App\Http\Controllers\RegistrarController::class, 'approveApplication'])->name('applications.approve');
    Route::post('/applications/{application}/reject', [App\Http\Controllers\RegistrarController::class, 'rejectApplication'])->name('applications.reject');
    
    // Deferment Management
    Route::get('/deferments', [App\Http\Controllers\RegistrarController::class, 'deferments'])->name('deferments');
    Route::post('/deferments/{deferment}/approve', [App\Http\Controllers\RegistrarController::class, 'approveDeferment'])->name('deferments.approve');
    Route::post('/deferments/{deferment}/reject', [App\Http\Controllers\RegistrarController::class, 'rejectDeferment'])->name('deferments.reject');
    Route::post('/deferments/{deferment}/reactivate', [App\Http\Controllers\RegistrarController::class, 'reactivateStudent'])->name('deferments.reactivate');
});

// Bank Routes
Route::middleware(['auth', 'role:bank'])->prefix('bank')->name('bank.')->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\BankController::class, 'dashboard'])->name('dashboard');
    Route::get('/users/create', [App\Http\Controllers\BankController::class, 'createUser'])->name('users.create');
    Route::post('/users', [App\Http\Controllers\BankController::class, 'storeUser'])->name('users.store');
    Route::get('/users/{user}/receipt', [App\Http\Controllers\BankController::class, 'downloadReceipt'])->name('users.receipt');
});

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

// Public admission form
Route::get('/admission', function () {
    $departments = \App\Models\Department::where('is_active', true)
        ->with(['activePrograms' => function($query) {
            $query->orderBy('sort_order');
        }])
        ->orderBy('sort_order')
        ->get();
    
    // Fetch all active programs for order of preferences
    $allPrograms = \App\Models\Program::where('is_active', true)
        ->orderBy('name')
        ->pluck('name')
        ->toArray();
    
    // Fetch dynamic sessions, campuses, and intakes
    $sessions = \App\Models\Session::active()->ordered()->get();
    $campuses = \App\Models\Campus::active()->ordered()->get();
    $intakes = \App\Models\Intake::active()->ordered()->get();
    
    return view('admission.form', compact('departments', 'allPrograms', 'sessions', 'campuses', 'intakes'));
})->name('admission.form');

// Public registration (buy form)
Route::get('/registration', [RegistrationController::class, 'show'])->name('registration.create');
Route::post('/registration', [RegistrationController::class, 'store'])->name('registration.store');

// Payment routes
Route::post('/payment/initiate', [App\Http\Controllers\PaymentController::class, 'initiatePayment'])->name('payment.initiate');
Route::get('/payment/success', [App\Http\Controllers\PaymentController::class, 'paymentSuccess'])->name('payment.success');
Route::get('/payment/cancelled', [App\Http\Controllers\PaymentController::class, 'paymentCancelled'])->name('payment.cancelled');
Route::post('/payment/ipn', [App\Http\Controllers\PaymentController::class, 'handleIpn'])->name('payment.ipn');

// Portal (user)
Route::middleware(['auth'])->prefix('portal')->name('portal.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'dashboard'])->name('dashboard');
    Route::get('/application', [DashboardController::class, 'applicationForm'])->name('application');
    Route::post('/application/save', [DashboardController::class, 'applicationSave'])->name('application.save');
    Route::post('/application/submit', [DashboardController::class, 'applicationSubmit'])->name('application.submit');
    Route::get('/application/print', [DashboardController::class, 'applicationPrint'])->name('application.print');
    Route::get('/results', [DashboardController::class, 'results'])->name('results');
    Route::post('/waec/fetch', [\App\Http\Controllers\WaecController::class, 'fetchResults'])->name('waec.fetch');
});

// SIP Login Redirect (for convenience)
Route::get('/sip/login', function () {
    return redirect()->route('login')->with('info', 'Please login to access the Student Information Portal.');
})->name('sip.login');

// SIP Portal Routes
Route::middleware(['auth'])->prefix('sip')->name('sip.')->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\SIPController::class, 'dashboard'])->name('dashboard');
    Route::get('/profile', [App\Http\Controllers\SIPController::class, 'profile'])->name('profile');
    Route::get('/academic-records', [App\Http\Controllers\SIPController::class, 'academicRecords'])->name('academic-records');
    Route::get('/downloads', [App\Http\Controllers\SIPController::class, 'downloads'])->name('downloads');
    Route::get('/downloads/{download}/file', [App\Http\Controllers\SIPController::class, 'downloadDocument'])->name('downloads.file');
    
    // Payments
    Route::prefix('payments')->name('payments.')->group(function () {
        Route::get('/invoices', [App\Http\Controllers\SIPPaymentController::class, 'invoices'])->name('invoices');
        Route::get('/history', [App\Http\Controllers\SIPPaymentController::class, 'paymentHistory'])->name('history');
        Route::get('/pay/{invoice}', [App\Http\Controllers\SIPPaymentController::class, 'showPaymentForm'])->name('pay');
        Route::post('/pay/{invoice}', [App\Http\Controllers\SIPPaymentController::class, 'processPayment'])->name('process');
    });
    
    // Course Registration
    Route::prefix('course-registration')->name('course-registration.')->group(function () {
        Route::get('/', [App\Http\Controllers\SIPCourseRegistrationController::class, 'showRegistrationForm'])->name('show');
        Route::post('/', [App\Http\Controllers\SIPCourseRegistrationController::class, 'registerCourses'])->name('register');
        Route::get('/list', [App\Http\Controllers\SIPCourseRegistrationController::class, 'showRegisteredCourses'])->name('list');
    });
    
    // Exam PIN
    Route::prefix('exam')->name('exam.')->group(function () {
        Route::get('/pins', [App\Http\Controllers\SIPExamController::class, 'viewExamPins'])->name('pins');
        Route::post('/generate-pin', [App\Http\Controllers\SIPExamController::class, 'generateExamPin'])->name('generate-pin');
    });
    
    // Deferment
    Route::prefix('deferment')->name('deferment.')->group(function () {
        Route::get('/', [App\Http\Controllers\SIPDefermentController::class, 'showDefermentForm'])->name('form');
        Route::post('/', [App\Http\Controllers\SIPDefermentController::class, 'submitDeferment'])->name('submit');
        Route::get('/status', [App\Http\Controllers\SIPDefermentController::class, 'viewDefermentStatus'])->name('status');
    });
});

// Staff WAEC utilities (Admin, HOD, Registrar, President)
Route::middleware(['auth', 'staff'])->prefix('waec')->name('waec.')->group(function () {
    Route::get('/search', [\App\Http\Controllers\WaecController::class, 'searchForm'])->name('search');
    Route::post('/fetch', [\App\Http\Controllers\WaecController::class, 'fetchResults'])->name('fetch');
    Route::get('/export', [\App\Http\Controllers\WaecController::class, 'exportCsv'])->name('export');
});
