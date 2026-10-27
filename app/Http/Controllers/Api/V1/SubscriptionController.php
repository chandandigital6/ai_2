<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\GrowyaSubscriptionController as DomainController;
use App\Services\Api\ApiActionService;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function __construct(private ApiActionService $actions) {}

    /** Shared operation: App\Http\Controllers\GrowyaSubscriptionController@adminIndex. */
    public function adminIndex(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'adminIndex');
    }

    /** Shared operation: App\Http\Controllers\GrowyaSubscriptionController@index. */
    public function index(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'index');
    }

    /** Shared operation: App\Http\Controllers\GrowyaSubscriptionController@request. */
    public function request(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'request');
    }

    /** Shared operation: App\Http\Controllers\GrowyaSubscriptionController@review. */
    public function review(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'review');
    }
}
