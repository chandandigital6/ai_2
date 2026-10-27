<?php

namespace App\Http\Controllers\Api\V1\Extensions;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Extensions\CommerceController as DomainController;
use App\Services\Api\ApiActionService;
use Illuminate\Http\Request;

class CommerceController extends Controller
{
    public function __construct(private ApiActionService $actions) {}

    /** Shared operation: App\Http\Controllers\Extensions\CommerceController@show. */
    public function show(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'show', adaptResponse: false);
    }

    /** Shared operation: App\Http\Controllers\Extensions\CommerceController@save. */
    public function save(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'save', adaptResponse: false);
    }
}
