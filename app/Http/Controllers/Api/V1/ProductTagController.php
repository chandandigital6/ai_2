<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ProductTagController as DomainController;
use App\Services\Api\ApiActionService;
use Illuminate\Http\Request;

class ProductTagController extends Controller
{
    public function __construct(private ApiActionService $actions) {}

    /** Shared operation: App\Http\Controllers\ProductTagController@index. */
    public function index(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'index');
    }

    /** Shared operation: App\Http\Controllers\ProductTagController@update. */
    public function update(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'update');
    }
}
