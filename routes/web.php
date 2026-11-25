<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\SessionController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::view('/entrar', 'login')->name('login');
    Route::post('/entrar', [SessionController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function (): void {
    Route::view('/', 'dashboard')->name('dashboard');
    Route::resource('categorias', CategoryController::class)
        ->parameters(['categorias' => 'category'])
        ->names('categories')
        ->except(['show', 'create']);
    Route::post('/sair', [SessionController::class, 'destroy'])->name('logout');
});
