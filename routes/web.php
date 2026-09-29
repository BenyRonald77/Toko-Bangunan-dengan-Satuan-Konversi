<?php

use App\Livewire\Dashboard;
use App\Livewire\Customers\CustomerForm;
use App\Livewire\Customers\CustomerIndex;
use App\Livewire\Products\ProductForm;
use App\Livewire\Products\ProductIndex;
use App\Livewire\Receivables\ReceivableIndex;
use App\Livewire\Receivables\ReceivableShow;
use App\Livewire\Sales\SaleForm;
use App\Livewire\Sales\SaleIndex;
use App\Livewire\Sales\SaleShow;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::get('dashboard', Dashboard::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

Route::middleware(['auth', 'verified'])->group(function () {
    // Produk & satuan konversi & harga grosir: admin only (lihat PRD bagian 7).
    Route::middleware('role:admin')->prefix('produk')->name('products.')->group(function () {
        Route::get('/', ProductIndex::class)->name('index');
        Route::get('/tambah', ProductForm::class)->name('create');
        Route::get('/{product}/edit', ProductForm::class)->name('edit');
    });

    // Pelanggan: admin only.
    Route::middleware('role:admin')->prefix('pelanggan')->name('customers.')->group(function () {
        Route::get('/', CustomerIndex::class)->name('index');
        Route::get('/tambah', CustomerForm::class)->name('create');
        Route::get('/{customer}/edit', CustomerForm::class)->name('edit');
    });

    // Penjualan: admin & kasir boleh membuat dan melihat.
    Route::middleware('role:admin,kasir')->prefix('penjualan')->name('sales.')->group(function () {
        Route::get('/', SaleIndex::class)->name('index');
        Route::get('/baru', SaleForm::class)->name('create');
        Route::get('/{sale}', SaleShow::class)->name('show');
    });

    // Piutang: admin & kasir boleh melihat dan mencatat pembayaran (lihat PRD bagian 7).
    Route::middleware('role:admin,kasir')->prefix('piutang')->name('receivables.')->group(function () {
        Route::get('/', ReceivableIndex::class)->name('index');
        Route::get('/{receivable}', ReceivableShow::class)->name('show');
    });
});

require __DIR__.'/auth.php';
