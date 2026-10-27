<?php

namespace App\Http\Controllers\Api\V1\Extensions;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Extensions\GatewayController as DomainController;
use App\Services\Api\ApiActionService;
use Illuminate\Http\Request;

class GatewayController extends Controller
{
    public function __construct(private ApiActionService $actions) {}

    /** Shared operation: App\Http\Controllers\Extensions\GatewayController@create. */
    public function create(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'create', adaptResponse: false);
    }

    /** Shared operation: App\Http\Controllers\Extensions\GatewayController@verify. */
    public function verify(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'verify', adaptResponse: false);
    }

    /** Shared operation: App\Http\Controllers\Extensions\GatewayController@index. */
    public function index(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'index', adaptResponse: false);
    }

    /** Shared operation: App\Http\Controllers\Extensions\GatewayController@reconcile. */
    public function reconcile(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'reconcile', adaptResponse: false);
    }

    /** Shared operation: App\Http\Controllers\Extensions\GatewayController@webhook. */
    public function webhook(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'webhook', adaptResponse: false);
    }
}
