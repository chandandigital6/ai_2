<?php

namespace App\Http\Controllers\Api\V1\Extensions;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Extensions\ActivityController as DomainController;
use App\Services\Api\ApiActionService;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    public function __construct(private ApiActionService $actions) {}

    /** Shared operation: App\Http\Controllers\Extensions\ActivityController@store. */
    public function store(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'store', adaptResponse: false);
    }

    /** Shared operation: App\Http\Controllers\Extensions\ActivityController@history. */
    public function history(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'history', adaptResponse: false);
    }

    /** Shared operation: App\Http\Controllers\Extensions\ActivityController@clear. */
    public function clear(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'clear', adaptResponse: false);
    }

    /** Shared operation: App\Http\Controllers\Extensions\ActivityController@share. */
    public function share(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'share', adaptResponse: false);
    }

    /** Shared operation: App\Http\Controllers\Extensions\ActivityController@resolve. */
    public function resolve(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'resolve', adaptResponse: false);
    }

    /** Shared operation: App\Http\Controllers\Extensions\ActivityController@search. */
    public function search(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'search', adaptResponse: false);
    }

    /** Shared operation: App\Http\Controllers\Extensions\ActivityController@daily. */
    public function daily(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'daily', adaptResponse: false);
    }
}
