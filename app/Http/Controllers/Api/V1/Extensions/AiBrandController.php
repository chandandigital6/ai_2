<?php

namespace App\Http\Controllers\Api\V1\Extensions;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Extensions\AiBrandController as DomainController;
use App\Services\Api\ApiActionService;
use Illuminate\Http\Request;

class AiBrandController extends Controller
{
    public function __construct(private ApiActionService $actions) {}

    /** Shared operation: App\Http\Controllers\Extensions\AiBrandController@index. */
    public function index(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'index', adaptResponse: false);
    }

    /** Shared operation: App\Http\Controllers\Extensions\AiBrandController@profile. */
    public function profile(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'profile', adaptResponse: false);
    }

    /** Shared operation: App\Http\Controllers\Extensions\AiBrandController@upload. */
    public function upload(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'upload', adaptResponse: false);
    }

    /** Shared operation: App\Http\Controllers\Extensions\AiBrandController@asset. */
    public function asset(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'asset', adaptResponse: false);
    }

    /** Shared operation: App\Http\Controllers\Extensions\AiBrandController@deleteAsset. */
    public function deleteAsset(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'deleteAsset', adaptResponse: false);
    }

    /** Shared operation: App\Http\Controllers\Extensions\AiBrandController@storePreset. */
    public function storePreset(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'storePreset', adaptResponse: false);
    }

    /** Shared operation: App\Http\Controllers\Extensions\AiBrandController@updatePreset. */
    public function updatePreset(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'updatePreset', adaptResponse: false);
    }

    /** Shared operation: App\Http\Controllers\Extensions\AiBrandController@deletePreset. */
    public function deletePreset(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'deletePreset', adaptResponse: false);
    }
}
