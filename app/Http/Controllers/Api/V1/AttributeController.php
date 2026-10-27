<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\AttributeController as DomainController;
use App\Http\Controllers\Controller;
use App\Services\Api\ApiActionService;
use Illuminate\Http\Request;

class AttributeController extends Controller
{
    public function __construct(private ApiActionService $actions) {}

    /** Shared operation: App\Http\Controllers\AttributeController@index. */
    public function index(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'index');
    }

    /** Shared operation: App\Http\Controllers\AttributeController@store. */
    public function store(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'store');
    }

    /** Shared operation: App\Http\Controllers\AttributeController@update. */
    public function update(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'update');
    }

    /** Shared operation: App\Http\Controllers\AttributeController@destroy. */
    public function destroy(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'destroy');
    }

    /** Shared operation: App\Http\Controllers\AttributeController@storeValue. */
    public function storeValue(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'storeValue');
    }

    /** Shared operation: App\Http\Controllers\AttributeController@updateValue. */
    public function updateValue(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'updateValue');
    }

    /** Shared operation: App\Http\Controllers\AttributeController@destroyValue. */
    public function destroyValue(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'destroyValue');
    }
}
