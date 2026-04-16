<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\FacebookController;
Route::get('/', function () {
    return view('auth.login');
});
Route::middleware(['auth', 'admin'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [AdminUserController::class, 'index'])->name('admin.dashboard');
    Route::post('/users', [AdminUserController::class, 'store'])->name('admin.users.store');
});
Route::get('/dashboard', function () {
    return view('subscriber/dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');
Route::get('/dashboard/subscriber', [AdminUserController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('subscriber.dashboard');
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth'])->group(function () {

    Route::post('/posts', [PostController::class, 'store'])->name('posts.store');
    // Route::get('/auth/facebook', [FacebookController::class, 'redirect'])->name('facebook.redirect');
    // Route::get('/auth/facebook/callback', [FacebookController::class, 'callback'])->name('facebook.callback');
});
Route::middleware('auth')->group(function () {
    Route::get('/facebook/redirect', [FacebookController::class, 'redirect'])->name('facebook.redirect');
    Route::get('/facebook/callback', [FacebookController::class, 'callback'])->name('facebook.callback');
});

Route::post('/pages', [PostController::class, 'store2'])->name('pages.store2')->middleware('auth');


require __DIR__.'/auth.php';
