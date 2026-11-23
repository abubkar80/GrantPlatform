<?php

use App\Http\Controllers\Admin\ApplicationReviewController;
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
        Route::post('application/reopen', [ApplicationController::class, 'reopen'])->name('application.reopen');
    });

    Route::middleware('can:application.review')->prefix('admin/applications')->name('admin.applications.')->group(function () {
        Route::get('/', [ApplicationReviewController::class, 'index'])->name('index');
        Route::get('{application}', [ApplicationReviewController::class, 'show'])->whereNumber('application')->name('show');
        Route::post('{application}/start', [ApplicationReviewController::class, 'start'])->whereNumber('application')->name('start');
        Route::post('{application}/assessment', [ApplicationReviewController::class, 'assess'])->whereNumber('application')->name('assess');
        Route::post('{application}/decision', [ApplicationReviewController::class, 'decide'])
            ->middleware('can:application.decide')->whereNumber('application')->name('decide');
        Route::get('{application}/documents/{document}', [ApplicationReviewController::class, 'document'])
            ->whereNumber(['application', 'document'])->name('documents.show');
    });

    Route::middleware('can:donor.approve')->prefix('admin/donors')->name('admin.donors.')->group(function () {
        Route::get('/', [DonorApprovalController::class, 'index'])->name('index');
        Route::post('{profile}/approve', [DonorApprovalController::class, 'approve'])->whereNumber('profile')->name('approve');
        Route::post('{profile}/decline', [DonorApprovalController::class, 'decline'])->whereNumber('profile')->name('decline');
    });
});

require __DIR__.'/settings.php';
