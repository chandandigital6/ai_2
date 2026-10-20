<?php

use App\Http\Controllers\Extensions\ActivityController;
use App\Http\Controllers\Extensions\SocialController;
use App\Http\Controllers\Extensions\WorkspaceController;
use Illuminate\Support\Facades\Route;

Route::get('integrations/meta/callback', [SocialController::class, 'callback'])->middleware('throttle:30,1')->name('extensions.meta.callback');
Route::get('s/{code}', [ActivityController::class, 'resolve'])->middleware('throttle:60,1')->name('extensions.share.resolve');
Route::get('businesses/{business}/workspace/{section?}', [WorkspaceController::class, 'businessWorkspace'])->middleware('auth')->name('extensions.workspace');
Route::get('admin/module-tools/{kind?}', [WorkspaceController::class, 'adminWorkspace'])->middleware('auth')->name('extensions.admin');
foreach (require __DIR__.'/extension_actions.php' as [$verb,$uri,$action,$name,$public,$web,$limit]) {
    if (! $web) {
        continue;
    }[$class,$method] = explode('@', $action);
    $middleware = ['throttle:'.$limit];
    if (! $public) {
        $middleware[] = 'auth';
    }
    Route::match([$verb], $uri, [$class, $method])->middleware($middleware)->name('extensions.'.$name);
}
