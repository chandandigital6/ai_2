<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ProductInventoryController as DomainController;
use App\Services\Api\ApiActionService;
use Illuminate\Http\Request;

class ProductInventoryController extends Controller
{
    public function __construct(private ApiActionService $actions) {}

    /** Shared operation: App\Http\Controllers\ProductInventoryController@index. */
    public function index(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'index');
    }

    /** Shared operation: App\Http\Controllers\ProductInventoryController@store. */
    public function store(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'store');
    }
}
