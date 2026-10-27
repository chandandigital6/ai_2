<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\GrowyaFinanceController as DomainController;
use App\Services\Api\ApiActionService;
use Illuminate\Http\Request;

class FinanceController extends Controller
{
    public function __construct(private ApiActionService $actions) {}

    /** Shared operation: App\Http\Controllers\GrowyaFinanceController@index. */
    public function index(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'index');
    }

    /** Shared operation: App\Http\Controllers\GrowyaFinanceController@store. */
    public function store(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'store');
    }

    /** Shared operation: App\Http\Controllers\GrowyaFinanceController@storeCategory. */
    public function storeCategory(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'storeCategory');
    }

    /** Shared operation: App\Http\Controllers\GrowyaFinanceController@export. */
    public function export(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'export');
    }

    /** Shared operation: App\Http\Controllers\GrowyaFinanceController@update. */
    public function update(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'update');
    }

    /** Shared operation: App\Http\Controllers\GrowyaFinanceController@destroy. */
    public function destroy(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'destroy');
    }
}
