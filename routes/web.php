<?php

use App\Http\Controllers\LogoutController;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return redirect('/login');
});

Route::group(['middleware' => 'guest'], function () {
    Volt::route('/login', 'login')->name('login');
});

Route::group(['middleware' => 'auth'], function () {
    Route::get('/logout', LogoutController::class)->name('logout');

    Volt::route('/dashboard', 'dashboard')->middleware('can:dashboard')->name('dashboard');

    Route::prefix('master')->group(function () {
        Volt::route('/categories', 'categories.index')->middleware('can:manage-categories')->name('categories');
        Volt::route('/products', 'products.index')->middleware('can:manage-products')->name('products');
        Volt::route('/users', 'users.index')->middleware('can:manage-users')->name('users');
    });

    Route::prefix('options')->group(function () {
        Volt::route('/roles', 'settings.roles.index')->middleware('can:manage-roles')->name('roles');
        Volt::route('/permissions', 'settings.permissions.index')->middleware('can:manage-permissions')->name('permissions');
    });
});
