<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\GrowyaPlanEntitlementController as DomainController;
use App\Services\Api\ApiActionService;
use Illuminate\Http\Request;

class PlanEntitlementController extends Controller
{
    public function __construct(private ApiActionService $actions) {}

    /** Shared operation: App\Http\Controllers\GrowyaPlanEntitlementController@usage. */
    public function usage(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'usage');
    }

    /** Shared operation: App\Http\Controllers\GrowyaPlanEntitlementController@edit. */
    public function edit(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'edit');
    }

    /** Shared operation: App\Http\Controllers\GrowyaPlanEntitlementController@update. */
    public function update(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'update');
    }
}
