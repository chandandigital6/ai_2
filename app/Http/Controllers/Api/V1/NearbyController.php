<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\GrowyaNearbyController as DomainController;
use App\Services\Api\ApiActionService;
use Illuminate\Http\Request;

class NearbyController extends Controller
{
    public function __construct(private ApiActionService $actions) {}

    /** Shared operation: App\Http\Controllers\GrowyaNearbyController@index. */
    public function index(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'index');
    }

    /** Shared operation: App\Http\Controllers\GrowyaNearbyController@edit. */
    public function edit(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'edit');
    }

    /** Shared operation: App\Http\Controllers\GrowyaNearbyController@update. */
    public function update(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'update');
    }
}
