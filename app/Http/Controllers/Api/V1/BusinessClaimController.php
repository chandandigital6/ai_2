<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\GrowyaBusinessClaimController as DomainController;
use App\Services\Api\ApiActionService;
use Illuminate\Http\Request;

class BusinessClaimController extends Controller
{
    public function __construct(private ApiActionService $actions) {}

    /** Shared operation: App\Http\Controllers\GrowyaBusinessClaimController@mine. */
    public function mine(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'mine');
    }

    /** Shared operation: App\Http\Controllers\GrowyaBusinessClaimController@admin. */
    public function admin(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'admin');
    }

    /** Shared operation: App\Http\Controllers\GrowyaBusinessClaimController@show. */
    public function show(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'show');
    }

    /** Shared operation: App\Http\Controllers\GrowyaBusinessClaimController@create. */
    public function create(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'create');
    }

    /** Shared operation: App\Http\Controllers\GrowyaBusinessClaimController@store. */
    public function store(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'store');
    }

    /** Shared operation: App\Http\Controllers\GrowyaBusinessClaimController@review. */
    public function review(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'review');
    }

    /** Shared operation: App\Http\Controllers\GrowyaBusinessClaimController@document. */
    public function document(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'document');
    }
}
