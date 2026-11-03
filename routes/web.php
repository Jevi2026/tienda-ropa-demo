<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\CashController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Store\CartController;
use App\Http\Controllers\Store\CheckoutController;
use App\Http\Controllers\Store\StoreController;
use Illuminate\Support\Facades\Route;

// Tienda pública
Route::get('/', [StoreController::class, 'home'])->name('home');
Route::get('/catalogo', [StoreController::class, 'catalog'])->name('catalog');
Route::get('/producto/{product:slug}', [StoreController::class, 'product'])->name('product');

Route::get('/carrito', [CartController::class, 'show'])->name('cart');
Route::post('/carrito', [CartController::class, 'add'])->name('cart.add');
Route::patch('/carrito/{variant}', [CartController::class, 'update'])->name('cart.update');
Route::delete('/carrito/{variantId}', [CartController::class, 'remove'])->whereNumber('variantId')->name('cart.remove');

Route::get('/finalizar', [CheckoutController::class, 'show'])->name('checkout');
Route::post('/finalizar', [CheckoutController::class, 'store'])->middleware('throttle:10,1')->name('checkout.store');
Route::get('/pedido/{order:public_token}', [CheckoutController::class, 'confirmation'])->name('order.public');

// Panel de administración
Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('/login', [AuthController::class, 'show'])->name('login');
        Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1')->name('login.attempt');
    });

    Route::middleware('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

        Route::get('/', DashboardController::class)->name('dashboard');

        Route::get('/inventario', [ProductController::class, 'index'])->name('products.index');
        Route::get('/inventario/nuevo', [ProductController::class, 'create'])->name('products.create');
        Route::post('/inventario', [ProductController::class, 'store'])->name('products.store');
        Route::get('/inventario/{product}/editar', [ProductController::class, 'edit'])->name('products.edit');
        Route::put('/inventario/{product}', [ProductController::class, 'update'])->name('products.update');

        Route::get('/pedidos', [OrderController::class, 'index'])->name('orders.index');
        Route::get('/pedidos/nuevo', [OrderController::class, 'create'])->name('orders.create');
        Route::post('/pedidos', [OrderController::class, 'store'])->name('orders.store');
        Route::get('/pedidos/{order}', [OrderController::class, 'show'])->name('orders.show');
        Route::post('/pedidos/{order}/pagos', [OrderController::class, 'pay'])->name('orders.pay');
        Route::post('/pedidos/{order}/entregar', [OrderController::class, 'deliver'])->name('orders.deliver');
        Route::post('/pedidos/{order}/cancelar', [OrderController::class, 'cancel'])->name('orders.cancel');

        Route::get('/caja', [CashController::class, 'index'])->name('cash.index');
        Route::post('/caja/cerrar', [CashController::class, 'close'])->name('cash.close');
        Route::post('/caja/movimientos', [CashController::class, 'movement'])->name('cash.movement');
        Route::post('/caja/{closing}/reabrir', [CashController::class, 'reopen'])->name('cash.reopen');
    });
});
