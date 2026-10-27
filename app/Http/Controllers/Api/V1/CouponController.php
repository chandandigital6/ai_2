<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\GrowyaCouponController as DomainController;
use App\Services\Api\ApiActionService;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    public function __construct(private ApiActionService $actions) {}

    /** Shared operation: App\Http\Controllers\GrowyaCouponController@index. */
    public function index(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'index');
    }

    /** Shared operation: App\Http\Controllers\GrowyaCouponController@store. */
    public function store(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'store');
    }

    /** Shared operation: App\Http\Controllers\GrowyaCouponController@create. */
    public function create(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'create');
    }

    /** Shared operation: App\Http\Controllers\GrowyaCouponController@update. */
    public function update(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'update');
    }

    /** Shared operation: App\Http\Controllers\GrowyaCouponController@edit. */
    public function edit(Request $request)
    {
        return $this->actions->respond($request, DomainController::class, 'edit');
    }
}
