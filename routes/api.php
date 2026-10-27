<?php

use App\Http\Controllers\Api\V1\AccountController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Middleware\EnsureActiveAccount;
use App\Http\Middleware\RequireGrowyaToken;
use Illuminate\Support\Facades\Route;

Route::get('user', [AuthController::class, 'me'])->middleware(['auth:sanctum', EnsureActiveAccount::class]);
Route::prefix('v1')->group(function () {
    Route::get('locations/{kind}', [AccountController::class, 'locations'])->whereIn('kind', ['countries', 'states', 'cities'])->middleware('throttle:60,1')->name('api.v1.locations');
    Route::post('auth/register', [AuthController::class, 'register'])->middleware('throttle:5,1')->name('api.v1.auth.register');
    Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:5,1')->name('api.v1.auth.login');
    Route::middleware(['auth:sanctum', EnsureActiveAccount::class, RequireGrowyaToken::class])->group(function () {
        Route::get('users', [AccountController::class, 'users'])->name('api.v1.users.index');
        Route::post('users', [AccountController::class, 'createUser'])->name('api.v1.users.store');
        Route::patch('users/{user}', [AccountController::class, 'updateUser'])->name('api.v1.users.update');
        Route::delete('users/{user}', [AccountController::class, 'deleteUser'])->name('api.v1.users.destroy');
        Route::patch('auth/profile', [AccountController::class, 'profile'])->name('api.v1.auth.profile');
        Route::put('auth/password', [AccountController::class, 'password'])->middleware('throttle:5,1')->name('api.v1.auth.password');
        Route::get('auth/me', [AuthController::class, 'me'])->name('api.v1.auth.me');
        Route::post('auth/logout', [AuthController::class, 'logout'])->name('api.v1.auth.logout');
        Route::get('businesses/{business}/my-permissions', [AccountController::class, 'permissions'])->name('api.v1.businesses.my-permissions');
    });
    require __DIR__.'/api/v1/routes.php';
});
