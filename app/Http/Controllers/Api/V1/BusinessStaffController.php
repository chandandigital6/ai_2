<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\BusinessStaffController as DomainController;
use App\Http\Controllers\Controller;
use App\Services\Api\ApiActionService;
use Illuminate\Http\Request;

class BusinessStaffController extends Controller
{
    public function __construct(private ApiActionService $actions) {}

    /** Shared operation: App\Http\Controllers\BusinessStaffController@index. */
    public function index(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'index');
    }

    /** Shared operation: App\Http\Controllers\BusinessStaffController@store. */
    public function store(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'store');
    }

    /** Shared operation: App\Http\Controllers\BusinessStaffController@history. */
    public function history(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'history');
    }

    /** Shared operation: App\Http\Controllers\BusinessStaffController@update. */
    public function update(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'update');
    }

    /** Shared operation: App\Http\Controllers\BusinessStaffController@destroy. */
    public function destroy(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'destroy');
    }
}
