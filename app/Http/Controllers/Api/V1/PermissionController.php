<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\PermissionController as DomainController;
use App\Services\Api\ApiActionService;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    public function __construct(private ApiActionService $actions) {}

    /** Shared operation: App\Http\Controllers\PermissionController@index. */
    public function index(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'index');
    }

    /** Shared operation: App\Http\Controllers\PermissionController@assign. */
    public function assign(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'assign');
    }

    /** Shared operation: App\Http\Controllers\PermissionController@store. */
    public function store(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'store');
    }

    /** Shared operation: App\Http\Controllers\PermissionController@destroy. */
    public function destroy(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'destroy');
    }
}
