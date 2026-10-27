<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\PublicGlobalSearchController as DomainController;
use App\Services\Api\ApiActionService;
use Illuminate\Http\Request;

class PublicGlobalSearchController extends Controller
{
    public function __construct(private ApiActionService $actions) {}

    /** Shared operation: App\Http\Controllers\PublicGlobalSearchController@index. */
    public function index(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'index');
    }

    /** Shared operation: App\Http\Controllers\PublicGlobalSearchController@suggest. */
    public function suggest(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'suggest');
    }
}
