<?php

use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\EmployeeActivationController;
use App\Http\Controllers\EmployeeAuthController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\PetController;
use App\Http\Controllers\BreedsController;
use App\Http\Controllers\PetHealthController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\SpeciesCategoriesController;
use App\Http\Controllers\TimeBlockController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\CustomerAppointmentController;


use Illuminate\Support\Facades\Route;


Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/verify-email', [AuthController::class, 'verifyEmail']);
    Route::post('/resend-otp', [AuthController::class, 'resendOtp']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);
    Route::post('/employee/complete-activation', [EmployeeActivationController::class, 'complete'])
        ->middleware(['audit'])
        ->name('employee.activation.complete');
    Route::post('/employee/login', [EmployeeAuthController::class, 'login'])
        ->middleware(['audit'])
        ->name('employee.auth.login');
    Route::middleware('auth:sanctum')->get('/me', [AuthController::class, 'me'])->name('auth.me');
    Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout'])->name('auth.logout');
});

Route::prefix('auth/admin')->middleware(['audit'])->group(function () {
    Route::post('/login', [AdminAuthController::class, 'login'])->name('admin.auth.login');
    Route::post('/google2fa/setup/confirm', [AdminAuthController::class, 'confirmSetup'])->name('admin.auth.2fa.setup');
    Route::post('/google2fa/challenge', [AdminAuthController::class, 'verifyChallenge'])->name('admin.auth.2fa.challenge');
});

Route::middleware(['auth:sanctum', 'admin', 'audit'])->prefix('admin')->group(function () {
    Route::apiResource('products', ProductController::class)->except(['create', 'edit']);
    Route::post('/employees', [EmployeeController::class, 'store'])->name('admin.employees.store');
    Route::put('/employees/{employee}', [EmployeeController::class, 'update'])->name('admin.employees.update');
    Route::post('/employees/{employee}/deactivate', [EmployeeController::class, 'deactivate'])->name('admin.employees.deactivate');
});

Route::middleware(['auth:sanctum', 'admin'])
    ->get('/admin/audit-logs', [AuditLogController::class, 'index'])
    ->name('admin.audit-logs.index');

Route::get('/admin/dashboard-metrics', [AuthController::class, 'adminMetrics'])
    ->middleware(['auth:sanctum', 'admin', 'audit'])
    ->name('admin.dashboard.metrics');

Route::middleware(['auth:sanctum', 'audit'])->group(function () {
    Route::get('appointments/pending', [AppointmentController::class, 'pendingList'])->name('appointments.pending-list');
    Route::get('appointments/pending-count', [AppointmentController::class, 'pendingCount'])->name('appointments.pending-count');
    Route::post('appointments/estimate', [AppointmentController::class, 'estimate'])->name('appointments.estimate');
    Route::get('/appointments/{appointment}/grooming-console', [AppointmentController::class, 'getGroomingConsole'])->name('appointments.grooming-console');
    Route::post('/appointments/{appointment}/finish-grooming', [AppointmentController::class, 'finishGrooming'])->name('appointments.finish-grooming');
    Route::get('/appointments', [AppointmentController::class, 'index'])->name('appointments.index');
    Route::get('/admin/employees', [EmployeeController::class, 'index'])->name('admin.employees.index');
    Route::get('/employee/appointments', [AppointmentController::class, 'myAppointments'])->name('employee.appointments');
    Route::put('/appointments/{appointment}', [AppointmentController::class, 'update'])->name('appointments.update');

    Route::post('/appointments', [AppointmentController::class, 'store'])->name('appointments.store');
    Route::post('/payments', [PaymentController::class, 'store'])->name('payments.store');

    // Clientes y Mascotas
    Route::apiResource('customers', CustomerController::class);
    Route::apiResource('pets', PetController::class);
    Route::apiResource('services', ServiceController::class);
    Route::apiResource('breeds', BreedsController::class);

    Route::apiResource('species-categories', SpeciesCategoriesController::class);
    Route::apiResource('time-blocks', TimeBlockController::class);


    // Salud de Mascotas
    Route::get('pets/{pet}/health', [PetHealthController::class, 'index']);
    Route::post('pets/{pet}/vaccinations', [PetHealthController::class, 'storeVaccination']);
    Route::post('pets/{pet}/photos', [PetHealthController::class, 'storePhoto']);

    // Solicitud de Citas Cliente
    Route::post('/customer/appointments/request', [CustomerAppointmentController::class, 'requestAppointment'])->name('customer.appointments.request');
    Route::get('/customer/appointments', [CustomerAppointmentController::class, 'getCustomerAppointments'])->name('customer.appointments.index');
});


