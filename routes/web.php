<?php

use App\Http\Controllers\EmployeeActivationWebController;
use App\Http\Controllers\GoogleAuthController;
use Illuminate\Support\Facades\Route;

Route::get('/auth/google/redirect', [GoogleAuthController::class, 'redirect'])->name('google.redirect');
Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('google.callback');

Route::get('/employee/activation/{user}', [EmployeeActivationWebController::class, 'redirect'])
    ->middleware(['signed'])
    ->name('employee.activation');

Route::get('/empleado/activar', fn () => view('application'));

Route::get('{any?}', function () {
    return view('application');
})->where('any', '.*');