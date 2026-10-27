<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\BusinessVerificationController as DomainController;
use App\Http\Controllers\Controller;
use App\Services\Api\ApiActionService;
use Illuminate\Http\Request;

class BusinessVerificationController extends Controller
{
    public function __construct(private ApiActionService $actions) {}

    /** Shared operation: App\Http\Controllers\BusinessVerificationController@index. */
    public function index(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'index');
    }

    /** Shared operation: App\Http\Controllers\BusinessVerificationController@show. */
    public function show(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'show');
    }

    /** Shared operation: App\Http\Controllers\BusinessVerificationController@approve. */
    public function approve(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'approve');
    }

    /** Shared operation: App\Http\Controllers\BusinessVerificationController@reject. */
    public function reject(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'reject');
    }

    /** Shared operation: App\Http\Controllers\BusinessVerificationController@pending. */
    public function pending(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'pending');
    }
}
