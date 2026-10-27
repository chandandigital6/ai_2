<?php

namespace App\Http\Controllers\Api\V1\Extensions;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Extensions\AiController as DomainController;
use App\Services\Api\ApiActionService;
use Illuminate\Http\Request;

class AiController extends Controller
{
    public function __construct(private ApiActionService $actions) {}

    /** Shared operation: App\Http\Controllers\Extensions\AiController@index. */
    public function index(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'index', adaptResponse: false);
    }

    /** Shared operation: App\Http\Controllers\Extensions\AiController@store. */
    public function store(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'store', adaptResponse: false);
    }

    /** Shared operation: App\Http\Controllers\Extensions\AiController@show. */
    public function show(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'show', adaptResponse: false);
    }

    /** Shared operation: App\Http\Controllers\Extensions\AiController@media. */
    public function media(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'media', adaptResponse: false);
    }

    /** Shared operation: App\Http\Controllers\Extensions\AiController@credits. */
    public function credits(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'credits', adaptResponse: false);
    }

    /** Shared operation: App\Http\Controllers\Extensions\AiController@grant. */
    public function grant(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'grant', adaptResponse: false);
    }
}
