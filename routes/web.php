<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\SupplierController;
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
    Route::resource('produtos', ProductController::class)
        ->parameters(['produtos' => 'product'])
        ->names('products')
        ->except('show');
    Route::resource('clientes', CustomerController::class)
        ->parameters(['clientes' => 'customer'])
        ->names('customers')
        ->except('destroy');
    Route::resource('fornecedores', SupplierController::class)
        ->parameters(['fornecedores' => 'supplier'])
        ->names('suppliers')
        ->except('show');
    Route::post('/sair', [SessionController::class, 'destroy'])->name('logout');
});
