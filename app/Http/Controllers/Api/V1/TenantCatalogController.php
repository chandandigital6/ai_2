<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\TenantCatalogController as DomainController;
use App\Services\Api\ApiActionService;
use Illuminate\Http\Request;

class TenantCatalogController extends Controller
{
    public function __construct(private ApiActionService $actions) {}

    /** Shared operation: App\Http\Controllers\TenantCatalogController@index. */
    public function index(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'index');
    }

    /** Shared operation: App\Http\Controllers\TenantCatalogController@store. */
    public function store(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'store');
    }

    /** Shared operation: App\Http\Controllers\TenantCatalogController@storeValue. */
    public function storeValue(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'storeValue');
    }

    /** Shared operation: App\Http\Controllers\TenantCatalogController@update. */
    public function update(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'update');
    }

    /** Shared operation: App\Http\Controllers\TenantCatalogController@destroy. */
    public function destroy(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'destroy');
    }

    /** Shared operation: App\Http\Controllers\TenantCatalogController@destroyValue. */
    public function destroyValue(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'destroyValue');
    }
}
