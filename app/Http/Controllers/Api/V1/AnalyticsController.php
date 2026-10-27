<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\GrowyaAnalyticsController as DomainController;
use App\Services\Api\ApiActionService;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function __construct(private ApiActionService $actions) {}

    /** Shared operation: App\Http\Controllers\GrowyaAnalyticsController@admin. */
    public function admin(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'admin');
    }

    /** Shared operation: App\Http\Controllers\GrowyaAnalyticsController@adminExport. */
    public function adminExport(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'adminExport');
    }

    /** Shared operation: App\Http\Controllers\GrowyaAnalyticsController@owner. */
    public function owner(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'owner');
    }

    /** Shared operation: App\Http\Controllers\GrowyaAnalyticsController@ownerExport. */
    public function ownerExport(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'ownerExport');
    }
}
