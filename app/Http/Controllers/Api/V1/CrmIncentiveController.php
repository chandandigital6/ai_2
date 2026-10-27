<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\GrowyaCrmIncentiveController as DomainController;
use App\Services\Api\ApiActionService;
use Illuminate\Http\Request;

class CrmIncentiveController extends Controller
{
    public function __construct(private ApiActionService $actions) {}

    /** Shared operation: App\Http\Controllers\GrowyaCrmIncentiveController@index. */
    public function index(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'index');
    }

    /** Shared operation: App\Http\Controllers\GrowyaCrmIncentiveController@report. */
    public function report(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'report');
    }

    /** Shared operation: App\Http\Controllers\GrowyaCrmIncentiveController@export. */
    public function export(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'export');
    }

    /** Shared operation: App\Http\Controllers\GrowyaCrmIncentiveController@rule. */
    public function rule(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'rule');
    }

    /** Shared operation: App\Http\Controllers\GrowyaCrmIncentiveController@claim. */
    public function claim(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'claim');
    }

    /** Shared operation: App\Http\Controllers\GrowyaCrmIncentiveController@toggleRule. */
    public function toggleRule(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'toggleRule');
    }

    /** Shared operation: App\Http\Controllers\GrowyaCrmIncentiveController@review. */
    public function review(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'review');
    }

    /** Shared operation: App\Http\Controllers\GrowyaCrmIncentiveController@pay. */
    public function pay(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'pay');
    }
}
