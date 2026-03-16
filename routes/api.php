<?php

use App\Http\Controllers\Api\Admin\AuthController;
use App\Http\Controllers\Api\Admin\CategoryController;
use App\Http\Controllers\Api\Admin\TopPersonController;
use App\Http\Controllers\Api\Admin\UserController;
use App\Http\Controllers\Api\PublicTopPersonController;
use Illuminate\Support\Facades\Route;

Route::get('top-people', [PublicTopPersonController::class, 'index'])->name('top-people.index');
Route::post('top-people', [PublicTopPersonController::class, 'store'])->name('top-people.store');

Route::prefix('admin')->name('admin.')->group(function (): void {
    Route::post('login', [AuthController::class, 'login'])->name('login');

    Route::middleware(['auth:sanctum', 'can:access-admin-api'])->group(function (): void {
        Route::get('me', [AuthController::class, 'me'])->name('me');
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');

        Route::apiResource('categories', CategoryController::class);
        Route::get('top-people/pending', [TopPersonController::class, 'pending'])->name('top-people.pending');
        Route::patch('top-people/{topPerson}/approve', [TopPersonController::class, 'approve'])->name('top-people.approve');
        Route::apiResource('top-people', TopPersonController::class);
        Route::apiResource('users', UserController::class);
    });
});
