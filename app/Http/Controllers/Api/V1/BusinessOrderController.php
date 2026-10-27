<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\GrowyaBusinessOrderController as DomainController;
use App\Services\Api\ApiActionService;
use Illuminate\Http\Request;

class BusinessOrderController extends Controller
{
    public function __construct(private ApiActionService $actions) {}

    /** Shared operation: App\Http\Controllers\GrowyaBusinessOrderController@index. */
    public function index(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'index');
    }

    /** Shared operation: App\Http\Controllers\GrowyaBusinessOrderController@show. */
    public function show(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'show');
    }

    /** Shared operation: App\Http\Controllers\GrowyaBusinessOrderController@status. */
    public function status(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'status');
    }
}
