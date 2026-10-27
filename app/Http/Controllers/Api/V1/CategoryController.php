<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\CategoryController as DomainController;
use App\Http\Controllers\Controller;
use App\Services\Api\ApiActionService;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function __construct(private ApiActionService $actions) {}

    /** Shared operation: App\Http\Controllers\CategoryController@index. */
    public function index(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'index');
    }

    /** Shared operation: App\Http\Controllers\CategoryController@store. */
    public function store(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'store');
    }

    /** Shared operation: App\Http\Controllers\CategoryController@create. */
    public function create(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'create');
    }

    /** Shared operation: App\Http\Controllers\CategoryController@update. */
    public function update(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'update');
    }

    /** Shared operation: App\Http\Controllers\CategoryController@destroy. */
    public function destroy(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'destroy');
    }

    /** Shared operation: App\Http\Controllers\CategoryController@edit. */
    public function edit(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'edit');
    }
}
