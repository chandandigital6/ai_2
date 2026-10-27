<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\RoleController as DomainController;
use App\Services\Api\ApiActionService;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function __construct(private ApiActionService $actions) {}

    /** Shared operation: App\Http\Controllers\RoleController@index. */
    public function index(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'index');
    }

    /** Shared operation: App\Http\Controllers\RoleController@store. */
    public function store(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'store');
    }

    /** Shared operation: App\Http\Controllers\RoleController@create. */
    public function create(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'create');
    }

    /** Shared operation: App\Http\Controllers\RoleController@show. */
    public function show(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'show');
    }

    /** Shared operation: App\Http\Controllers\RoleController@update. */
    public function update(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'update');
    }

    /** Shared operation: App\Http\Controllers\RoleController@destroy. */
    public function destroy(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'destroy');
    }

    /** Shared operation: App\Http\Controllers\RoleController@edit. */
    public function edit(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'edit');
    }

    /** Shared operation: App\Http\Controllers\RoleController@removePermission. */
    public function removePermission(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'removePermission');
    }
}
