<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ProductController as DomainController;
use App\Services\Api\ApiActionService;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct(private ApiActionService $actions) {}

    /** Shared operation: App\Http\Controllers\ProductController@index. */
    public function index(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'index');
    }

    /** Shared operation: App\Http\Controllers\ProductController@store. */
    public function store(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'store');
    }

    /** Shared operation: App\Http\Controllers\ProductController@create. */
    public function create(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'create');
    }

    /** Shared operation: App\Http\Controllers\ProductController@show. */
    public function show(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'show');
    }

    /** Shared operation: App\Http\Controllers\ProductController@update. */
    public function update(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'update');
    }

    /** Shared operation: App\Http\Controllers\ProductController@destroy. */
    public function destroy(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'destroy');
    }

    /** Shared operation: App\Http\Controllers\ProductController@edit. */
    public function edit(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'edit');
    }
}
