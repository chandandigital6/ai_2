<?php

namespace App\Http\Controllers\Api\V1\Extensions;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Extensions\AiPlanController as DomainController;
use App\Services\Api\ApiActionService;
use Illuminate\Http\Request;

class AiPlanController extends Controller
{
    public function __construct(private ApiActionService $actions) {}

    /** Shared operation: App\Http\Controllers\Extensions\AiPlanController@catalog. */
    public function catalog(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'catalog', adaptResponse: false);
    }

    /** Shared operation: App\Http\Controllers\Extensions\AiPlanController@requestPlan. */
    public function requestPlan(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'requestPlan', adaptResponse: false);
    }

    /** Shared operation: App\Http\Controllers\Extensions\AiPlanController@adminIndex. */
    public function adminIndex(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'adminIndex', adaptResponse: false);
    }

    /** Shared operation: App\Http\Controllers\Extensions\AiPlanController@store. */
    public function store(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'store', adaptResponse: false);
    }

    /** Shared operation: App\Http\Controllers\Extensions\AiPlanController@update. */
    public function update(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'update', adaptResponse: false);
    }

    /** Shared operation: App\Http\Controllers\Extensions\AiPlanController@review. */
    public function review(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'review', adaptResponse: false);
    }
}
