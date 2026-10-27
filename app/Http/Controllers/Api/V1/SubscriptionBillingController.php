<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\GrowyaSubscriptionBillingController as DomainController;
use App\Services\Api\ApiActionService;
use Illuminate\Http\Request;

class SubscriptionBillingController extends Controller
{
    public function __construct(private ApiActionService $actions) {}

    /** Shared operation: App\Http\Controllers\GrowyaSubscriptionBillingController@adminIndex. */
    public function adminIndex(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'adminIndex');
    }

    /** Shared operation: App\Http\Controllers\GrowyaSubscriptionBillingController@show. */
    public function show(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'show');
    }

    /** Shared operation: App\Http\Controllers\GrowyaSubscriptionBillingController@export. */
    public function export(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'export');
    }

    /** Shared operation: App\Http\Controllers\GrowyaSubscriptionBillingController@ownerIndex. */
    public function ownerIndex(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'ownerIndex');
    }

    /** Shared operation: App\Http\Controllers\GrowyaSubscriptionBillingController@pdf. */
    public function pdf(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'pdf');
    }

    /** Shared operation: App\Http\Controllers\GrowyaSubscriptionBillingController@issue. */
    public function issue(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'issue');
    }
}
