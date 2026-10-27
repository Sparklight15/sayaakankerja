<?php

use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::view('dashboard', 'dashboard')
    ->middleware('auth')
    ->name('dashboard');

require __DIR__.'/auth.php';
