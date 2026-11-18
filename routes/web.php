<?php

use App\Http\Controllers\Admin\DonorApprovalController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::middleware('can:donor.approve')->prefix('admin/donors')->name('admin.donors.')->group(function () {
        Route::get('/', [DonorApprovalController::class, 'index'])->name('index');
        Route::post('{profile}/approve', [DonorApprovalController::class, 'approve'])->whereNumber('profile')->name('approve');
        Route::post('{profile}/decline', [DonorApprovalController::class, 'decline'])->whereNumber('profile')->name('decline');
    });
});

require __DIR__.'/settings.php';
