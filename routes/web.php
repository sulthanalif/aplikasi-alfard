<?php

use App\Http\Controllers\LogoutController;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return redirect('/login');
});

Route::group(['middleware' => 'guest'], function () {
    Volt::route('/login', 'login')->name('login');
    Volt::route('/register', 'register')->name('register');
});

// Volt::route('/inactive', 'user-inactive')->name('user.inactive')->middleware('auth', 'user.active');

Route::group(['middleware' => ['auth', 'userAccess']], function () {
    Volt::route('/inactive', 'user-inactive')->name('user.inactive');
    Route::get('/logout', LogoutController::class)->name('logout');

    Volt::route('/dashboard', 'dashboard')->middleware('can:dashboard')->name('dashboard');

    Route::prefix('master')->group(function () {
        Volt::route('/categories', 'masters.categories.index')->middleware('can:manage-categories')->name('categories');
        Volt::route('/units', 'masters.units.index')->middleware('can:manage-products')->name('units');
        Volt::route('/products', 'masters.products.index')->middleware('can:manage-products')->name('products');
        Volt::route('/users', 'masters.users.index')->middleware('can:manage-users')->name('users');
    });

    Volt::route('/order', 'transactions.sales.index')->middleware('can:manage-order')->name('order');
    Volt::route('/order/form', 'transactions.sales.form')->middleware('can:manage-order')->name('order.form');
    Volt::route('/order/{sales:invoice}/detail', 'transactions.sales.detail')->middleware('can:manage-order')->name('order.detail');

    Route::prefix('transactions')->group(function () {
        Volt::route('/sales', 'transactions.sales.index')->middleware('can:manage-sales')->name('sales');
        Volt::route('/sales/form', 'transactions.sales.form')->middleware('can:manage-sales')->name('sales.form');
        Volt::route('/sales/{sales:invoice}/detail', 'transactions.sales.detail')->middleware('can:manage-sales')->name('sales.detail');

        Volt::route('/po', 'transactions.po.index')->middleware('can:manage-po')->name('po');
        Volt::route('/po/form', 'transactions.po.form')->middleware('can:manage-po')->name('po.form');
        Volt::route('/po/{po:invoice}/detail', 'transactions.po.detail')->middleware('can:manage-po')->name('po.detail');
    });

    Volt::route('/distributions', 'distributions.index')->middleware('can:manage-distribution')->name('distributions');
    Volt::route('/distributions/form', 'distributions.form')->middleware('can:manage-distribution')->name('distributions.form');
    Volt::route('/distributions/{distribution:number}/detail', 'distributions.detail')->middleware('can:manage-distribution')->name('distributions.detail');

    Route::prefix('options')->group(function () {
        Volt::route('/roles', 'settings.roles.index')->middleware('can:manage-roles')->name('roles');
        Volt::route('/permissions', 'settings.permissions.index')->middleware('can:manage-permissions')->name('permissions');
    });

    Route::prefix('reports')->group(function () {
        Volt::route('/sales', 'reports.sales.index')->middleware('can:sales-report')->name('sales-report');
        Volt::route('/expenditure', 'reports.po.index')->middleware('can:po-report')->name('po-report');
        Volt::route('/distribution', 'reports.distribution.index')->middleware('can:distribution-report')->name('distribution-report');
    });
});
