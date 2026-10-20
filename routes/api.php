<?php

use App\Http\Controllers\Api\V1\AccountController;
use App\Http\Controllers\Api\V1\ActionController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Middleware\EnsureActiveAccount;
use App\Http\Middleware\OptionalGrowyaToken;
use App\Http\Middleware\RequireGrowyaToken;
use App\Models\Business;
use App\Services\BusinessAccessService;
use Illuminate\Http\Request;
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
        Route::get('businesses/{business}/my-permissions', function (Request $request, Business $business) {
            $access = app(BusinessAccessService::class);
            abort_unless($access->can($request->user(), $business, 'business.view'), 403);

            return ['data' => collect(config('business_permissions.permissions'))->filter(fn ($permission) => $access->can($request->user(), $business, $permission))->values()];
        })->name('api.v1.businesses.my-permissions');
    });
    foreach (require __DIR__.'/api_actions.php' as [$verb,$uri,$action,$name,$middleware]) {
        $route = Route::match([$verb], $uri, ActionController::class)->middleware(array_merge(['throttle:120,1'], $middleware))->name($name);
        $route->setAction(array_merge($route->getAction(), ['domain_action' => $action]));
        if (str_contains($uri, '{kind}')) {
            $route->whereIn('kind', ['categories', 'attributes']);
        }
    }
    foreach (require __DIR__.'/extension_actions.php' as [$verb,$uri,$action,$name,$public,$web,$limit]) {
        [$class, $method] = explode('@', $action);
        $middleware = ['throttle:'.$limit];
        if (! $public) {
            $middleware = array_merge($middleware, ['auth:sanctum', EnsureActiveAccount::class, RequireGrowyaToken::class]);
        }
        if ($public && $verb === 'GET') {
            $middleware[] = OptionalGrowyaToken::class;
        }
        Route::match([$verb], $uri, [$class, $method])->middleware($middleware)->name('api.v1.extensions.'.$name);
    }
});
