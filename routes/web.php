<?php

use App\Http\Controllers\Admin\DonorApprovalController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Student\ApplicationController;
use App\Http\Controllers\Student\ProfileController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::middleware('can:application.edit-own')->prefix('student')->name('student.')->group(function () {
        Route::get('profile', [ProfileController::class, 'show'])->name('profile.show');
        Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');

        Route::get('application', [ApplicationController::class, 'show'])->name('application.show');
        Route::post('application/start', [ApplicationController::class, 'start'])->name('application.start');
        Route::put('application/draft', [ApplicationController::class, 'saveDraft'])->name('application.draft');
        Route::post('application/documents', [ApplicationController::class, 'upload'])->name('application.documents.store');
        Route::delete('application/documents/{document}', [ApplicationController::class, 'removeDocument'])
            ->whereNumber('document')->name('application.documents.destroy');
        Route::post('application/submit', [ApplicationController::class, 'submit'])->name('application.submit');
    });

    Route::middleware('can:donor.approve')->prefix('admin/donors')->name('admin.donors.')->group(function () {
        Route::get('/', [DonorApprovalController::class, 'index'])->name('index');
        Route::post('{profile}/approve', [DonorApprovalController::class, 'approve'])->whereNumber('profile')->name('approve');
        Route::post('{profile}/decline', [DonorApprovalController::class, 'decline'])->whereNumber('profile')->name('decline');
    });
});

require __DIR__.'/settings.php';
