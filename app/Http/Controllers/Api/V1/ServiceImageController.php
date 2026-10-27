<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ServiceImageController as DomainController;
use App\Services\Api\ApiActionService;
use Illuminate\Http\Request;

class ServiceImageController extends Controller
{
    public function __construct(private ApiActionService $actions) {}

    /** Shared operation: App\Http\Controllers\ServiceImageController@store. */
    public function store(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'store');
    }

    /** Shared operation: App\Http\Controllers\ServiceImageController@destroy. */
    public function destroy(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'destroy');
    }

    /** Shared operation: App\Http\Controllers\ServiceImageController@featured. */
    public function featured(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'featured');
    }
}
