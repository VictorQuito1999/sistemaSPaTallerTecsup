<?php

use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\EmployeeActivationController;
use App\Http\Controllers\EmployeeAuthController;
use App\Http\Controllers\EmployeeController;
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
    Route::get('/employees', [EmployeeController::class, 'index'])->name('admin.employees.index');
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
