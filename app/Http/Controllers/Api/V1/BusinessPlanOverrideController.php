<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\GrowyaBusinessPlanOverrideController as DomainController;
use App\Services\Api\ApiActionService;
use Illuminate\Http\Request;

class BusinessPlanOverrideController extends Controller
{
    public function __construct(private ApiActionService $actions) {}

    /** Shared operation: App\Http\Controllers\GrowyaBusinessPlanOverrideController@edit. */
    public function edit(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'edit');
    }

    /** Shared operation: App\Http\Controllers\GrowyaBusinessPlanOverrideController@save. */
    public function save(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'save');
    }

    /** Shared operation: App\Http\Controllers\GrowyaBusinessPlanOverrideController@remove. */
    public function remove(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'remove');
    }
}
