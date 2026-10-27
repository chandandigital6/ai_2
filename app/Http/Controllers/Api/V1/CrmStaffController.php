<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\GrowyaCrmStaffController as DomainController;
use App\Services\Api\ApiActionService;
use Illuminate\Http\Request;

class CrmStaffController extends Controller
{
    public function __construct(private ApiActionService $actions) {}

    /** Shared operation: App\Http\Controllers\GrowyaCrmStaffController@dashboard. */
    public function dashboard(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'dashboard');
    }

    /** Shared operation: App\Http\Controllers\GrowyaCrmStaffController@report. */
    public function report(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'report');
    }

    /** Shared operation: App\Http\Controllers\GrowyaCrmStaffController@target. */
    public function target(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'target');
    }

    /** Shared operation: App\Http\Controllers\GrowyaCrmStaffController@show. */
    public function show(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'show');
    }

    /** Shared operation: App\Http\Controllers\GrowyaCrmStaffController@assign. */
    public function assign(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'assign');
    }

    /** Shared operation: App\Http\Controllers\GrowyaCrmStaffController@progress. */
    public function progress(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'progress');
    }

    /** Shared operation: App\Http\Controllers\GrowyaCrmStaffController@activity. */
    public function activity(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'activity');
    }
}
