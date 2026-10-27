<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\GrowyaLeadInsightsController as DomainController;
use App\Services\Api\ApiActionService;
use Illuminate\Http\Request;

class LeadInsightsController extends Controller
{
    public function __construct(private ApiActionService $actions) {}

    /** Shared operation: App\Http\Controllers\GrowyaLeadInsightsController@admin. */
    public function admin(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'admin');
    }

    /** Shared operation: App\Http\Controllers\GrowyaLeadInsightsController@adminExport. */
    public function adminExport(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'adminExport');
    }

    /** Shared operation: App\Http\Controllers\GrowyaLeadInsightsController@owner. */
    public function owner(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'owner');
    }

    /** Shared operation: App\Http\Controllers\GrowyaLeadInsightsController@ownerExport. */
    public function ownerExport(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'ownerExport');
    }
}
